<?php

namespace App\Support\ApiDocs;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use ReflectionMethod;
use Throwable;

/**
 * Đọc schema query/body từ $request->validate([...]) trong controller method.
 */
class EndpointSchemaExtractor
{
    /** @var array<string, Node\Stmt[]> */
    private array $astCache = [];

    private PrettyPrinter $printer;

    public function __construct()
    {
        $this->printer = new PrettyPrinter;
    }

    /**
     * @return array{
     *     description: ?string,
     *     query_params: list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>,
     *     body_params: list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>,
     *     body_example: array<string, mixed>,
     *     query_example: array<string, mixed>
     * }
     */
    public function extract(?string $action, string $httpMethod): array
    {
        $empty = [
            'description' => null,
            'query_params' => [],
            'body_params' => [],
            'body_example' => [],
            'query_example' => [],
        ];

        if (! is_string($action) || ! str_contains($action, '@')) {
            return $empty;
        }

        [$class, $method] = explode('@', $action, 2);

        try {
            if (! class_exists($class) || ! method_exists($class, $method)) {
                return $empty;
            }

            $ref = new ReflectionMethod($class, $method);
            $description = $this->extractDocSummary($ref);
            $fields = $this->extractFieldsFromMethod($ref);

            // store/update gọi $this->validatePayload(...) — lấy rules từ helper
            if ($fields === [] && $this->methodCallsValidatePayload($ref)) {
                $fields = $this->extractFieldsFromNamedMethod($class, 'validatePayload');
            }

            // Ví dụ checkin → resolveClientIp() có validate('ip')
            if ($fields === []) {
                $fields = $this->extractFieldsFromCalledHelpers($ref);
            }

            $isBodyMethod = in_array(strtoupper($httpMethod), ['POST', 'PUT', 'PATCH'], true);

            $params = array_values(array_filter(
                $fields,
                fn (array $f) => ! str_ends_with($f['name'], '.*') && ! str_contains($f['name'], '.*.')
            ));

            $nestedHints = array_values(array_filter(
                $fields,
                fn (array $f) => str_contains($f['name'], '.')
            ));

            if ($isBodyMethod) {
                $bodyParams = $params;
                $bodyExample = $this->buildExample($bodyParams, $nestedHints);

                return [
                    'description' => $description,
                    'query_params' => [],
                    'body_params' => $bodyParams,
                    'body_example' => $bodyExample,
                    'query_example' => [],
                ];
            }

            return [
                'description' => $description,
                'query_params' => $params,
                'body_params' => [],
                'body_example' => [],
                'query_example' => $this->buildExample($params, []),
            ];
        } catch (Throwable) {
            return $empty;
        }
    }

    private function extractDocSummary(ReflectionMethod $ref): ?string
    {
        $doc = $ref->getDocComment();
        if (! is_string($doc)) {
            return null;
        }

        $lines = preg_split('/\R/', $doc) ?: [];
        $parts = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\/\*\*?|\*\/$/', '', $line) ?? '';
            $line = preg_replace('/^\*\s?/', '', $line) ?? '';
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '@')) {
                if ($line !== '' && str_starts_with($line, '@') && $parts !== []) {
                    break;
                }
                continue;
            }

            // Bỏ dòng "Query: ..." — schema đã liệt kê cụ thể
            if (preg_match('/^Query\s*:/i', $line)) {
                continue;
            }

            $parts[] = $line;
        }

        $summary = trim(implode(' ', $parts));

        return $summary !== '' ? $summary : null;
    }

    /**
     * @return list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>
     */
    private function extractFieldsFromMethod(ReflectionMethod $ref): array
    {
        $methodNode = $this->findMethodNode($ref);
        if (! $methodNode instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        return $this->fieldsFromValidateCalls($methodNode);
    }

    /**
     * @return list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>
     */
    private function extractFieldsFromNamedMethod(string $class, string $method): array
    {
        if (! method_exists($class, $method)) {
            return [];
        }

        $ref = new ReflectionMethod($class, $method);
        $methodNode = $this->findMethodNode($ref);
        if (! $methodNode instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        return $this->fieldsFromValidateCalls($methodNode);
    }

    private function methodCallsValidatePayload(ReflectionMethod $ref): bool
    {
        return in_array('validatePayload', $this->thisMethodCalls($ref), true);
    }

    /**
     * @return list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>
     */
    private function extractFieldsFromCalledHelpers(ReflectionMethod $ref): array
    {
        $class = $ref->getDeclaringClass()->getName();
        $merged = [];

        foreach ($this->thisMethodCalls($ref) as $helperName) {
            if (in_array($helperName, ['validate', 'validatePayload'], true)) {
                continue;
            }

            if (! method_exists($class, $helperName)) {
                continue;
            }

            $helperRef = new ReflectionMethod($class, $helperName);
            if ($helperRef->isPublic() && $helperRef->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($this->extractFieldsFromMethod($helperRef) as $field) {
                $merged[$field['name']] = $field;
            }
        }

        return array_values($merged);
    }

    /**
     * @return list<string>
     */
    private function thisMethodCalls(ReflectionMethod $ref): array
    {
        $methodNode = $this->findMethodNode($ref);
        if (! $methodNode instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        $finder = new NodeFinder;
        $calls = $finder->find($methodNode->stmts ?? [], function (Node $node) {
            if (! $node instanceof Node\Expr\MethodCall) {
                return false;
            }

            if (! $node->name instanceof Node\Identifier) {
                return false;
            }

            // $this->foo(...)
            return $node->var instanceof Node\Expr\Variable && $node->var->name === 'this';
        });

        $names = [];
        foreach ($calls as $call) {
            /** @var Node\Expr\MethodCall $call */
            $names[] = $call->name->toString();
        }

        return array_values(array_unique($names));
    }

    /**
     * @return list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>
     */
    private function fieldsFromValidateCalls(Node\Stmt\ClassMethod $methodNode): array
    {
        $finder = new NodeFinder;
        $calls = $finder->find($methodNode->stmts ?? [], function (Node $node) {
            if (! $node instanceof Node\Expr\MethodCall) {
                return false;
            }

            if (! $node->name instanceof Node\Identifier || $node->name->toString() !== 'validate') {
                return false;
            }

            // $request->validate(...) hoặc $this->validate(...)
            return true;
        });

        foreach ($calls as $call) {
            /** @var Node\Expr\MethodCall $call */
            $args = $call->args;
            if ($args === []) {
                continue;
            }

            $rulesExpr = $args[0]->value;
            if (! $rulesExpr instanceof Node\Expr\Array_) {
                continue;
            }

            $fields = $this->parseRulesArray($rulesExpr);
            if ($fields !== []) {
                return $this->expandConfirmedFields($fields);
            }
        }

        return [];
    }

    /**
     * @return list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>
     */
    private function parseRulesArray(Node\Expr\Array_ $array): array
    {
        $fields = [];

        foreach ($array->items as $item) {
            if (! $item instanceof Node\Expr\ArrayItem || $item->key === null) {
                continue;
            }

            $name = $this->scalarString($item->key);
            if ($name === null || $name === '') {
                continue;
            }

            $ruleParts = $this->normalizeRules($item->value);
            $required = $this->isRequired($ruleParts);
            $type = $this->inferType($ruleParts);
            $example = $this->inferExample($name, $type, $ruleParts);

            $fields[] = [
                'name' => $name,
                'required' => $required,
                'type' => $type,
                'rules' => $ruleParts,
                'example' => $example,
            ];
        }

        return $fields;
    }

    /**
     * Rule "confirmed" yêu cầu field {name}_confirmation dù không khai báo trong rules.
     *
     * @param  list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>  $fields
     * @return list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>
     */
    private function expandConfirmedFields(array $fields): array
    {
        $extra = [];
        $existing = array_column($fields, 'name');

        foreach ($fields as $field) {
            $hasConfirmed = false;
            foreach ($field['rules'] as $rule) {
                if ($rule === 'confirmed' || str_starts_with($rule, 'confirmed:')) {
                    $hasConfirmed = true;
                    break;
                }
            }

            if (! $hasConfirmed) {
                continue;
            }

            $confirmName = $field['name'].'_confirmation';
            if (in_array($confirmName, $existing, true)) {
                continue;
            }

            $extra[] = [
                'name' => $confirmName,
                'required' => $field['required'],
                'type' => $field['type'],
                'rules' => ['required_with:'.$field['name'], 'same:'.$field['name']],
                'example' => $field['example'],
            ];
        }

        return array_merge($fields, $extra);
    }

    /**
     * @return list<string>
     */
    private function normalizeRules(Node\Expr $expr): array
    {
        if ($expr instanceof Node\Scalar\String_) {
            return array_values(array_filter(array_map('trim', explode('|', $expr->value))));
        }

        if (! $expr instanceof Node\Expr\Array_) {
            return [$this->exprToRuleString($expr)];
        }

        $parts = [];
        foreach ($expr->items as $item) {
            if (! $item instanceof Node\Expr\ArrayItem) {
                continue;
            }
            $parts[] = $this->exprToRuleString($item->value);
        }

        return array_values(array_filter($parts, fn (string $r) => $r !== ''));
    }

    private function exprToRuleString(Node\Expr $expr): string
    {
        // Rule::in([...]) / Rule::unique(...)->ignore(...)
        if ($expr instanceof Node\Expr\StaticCall
            && $expr->class instanceof Node\Name
            && $expr->name instanceof Node\Identifier
        ) {
            $class = $expr->class->toString();
            $method = $expr->name->toString();

            if (str_ends_with($class, 'Rule') || $class === 'Rule') {
                if ($method === 'in') {
                    $values = $this->extractInValues($expr);

                    return $values !== [] ? 'in:'.implode(',', $values) : 'in';
                }

                if ($method === 'unique') {
                    $table = isset($expr->args[0]) ? $this->scalarString($expr->args[0]->value) : null;
                    $column = isset($expr->args[1]) ? $this->scalarString($expr->args[1]->value) : null;
                    $parts = array_filter([$table, $column]);

                    return $parts !== [] ? 'unique:'.implode(',', $parts) : 'unique';
                }

                return $method;
            }
        }

        // Password::defaults()
        if ($expr instanceof Node\Expr\StaticCall
            && $expr->class instanceof Node\Name
            && str_ends_with($expr->class->toString(), 'Password')
        ) {
            return 'password';
        }

        // $maHopDongRule (biến rule)
        if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
            return '$'.$expr->name;
        }

        // Method call chain: Rule::unique(...)->ignore(...)
        if ($expr instanceof Node\Expr\MethodCall) {
            $base = $expr->var;
            $baseStr = $this->exprToRuleString($base);
            $name = $expr->name instanceof Node\Identifier ? $expr->name->toString() : 'call';

            if ($name === 'ignore') {
                return $baseStr.' (ignore on update)';
            }

            return $baseStr;
        }

        if ($expr instanceof Node\Scalar\String_) {
            return $expr->value;
        }

        try {
            return trim($this->printer->prettyPrintExpr($expr));
        } catch (Throwable) {
            return 'custom';
        }
    }

    /**
     * @return list<string>
     */
    private function extractInValues(Node\Expr\StaticCall $call): array
    {
        if ($call->args === []) {
            return [];
        }

        $arg = $call->args[0]->value;
        if (! $arg instanceof Node\Expr\Array_) {
            return [];
        }

        $values = [];
        foreach ($arg->items as $item) {
            if (! $item instanceof Node\Expr\ArrayItem) {
                continue;
            }
            $v = $this->scalarString($item->value);
            if ($v !== null) {
                $values[] = $v;
            }
        }

        return $values;
    }

    private function scalarString(Node $node): ?string
    {
        if ($node instanceof Node\Scalar\String_) {
            return $node->value;
        }

        if ($node instanceof Node\Scalar\LNumber || $node instanceof Node\Scalar\DNumber) {
            return (string) $node->value;
        }

        return null;
    }

    /**
     * @param  list<string>  $rules
     */
    private function isRequired(array $rules): bool
    {
        $joined = implode('|', $rules);

        if (preg_match('/\brequired(_if|_unless|_with|_without|_with_all|_without_all)?\b/', $joined)) {
            return true;
        }

        // sometimes / nullable / optional → không bắt buộc
        return false;
    }

    /**
     * @param  list<string>  $rules
     */
    private function inferType(array $rules): string
    {
        $joined = strtolower(implode('|', $rules));

        if (preg_match('/\barray\b/', $joined) || str_contains($joined, '.*')) {
            return 'array';
        }
        if (preg_match('/\b(integer|int)\b/', $joined)) {
            return 'integer';
        }
        if (preg_match('/\b(numeric|decimal|float|double)\b/', $joined)) {
            return 'number';
        }
        if (preg_match('/\bboolean|bool\b/', $joined)) {
            return 'boolean';
        }
        if (preg_match('/\b(file|image|mimes)\b/', $joined)) {
            return 'file';
        }
        if (preg_match('/\bdate(_format)?\b/', $joined)) {
            return 'date';
        }
        if (preg_match('/\bemail\b/', $joined)) {
            return 'email';
        }
        if (preg_match('/\bin:/', $joined)) {
            return 'enum';
        }
        if (preg_match('/\bpassword\b/', $joined)) {
            return 'password';
        }

        return 'string';
    }

    /**
     * @param  list<string>  $rules
     */
    private function inferExample(string $name, string $type, array $rules): mixed
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'in:')) {
                $values = explode(',', substr($rule, 3));

                return $values[0] ?? null;
            }
        }

        return match ($type) {
            'integer' => str_contains($name, 'page') ? 1 : (str_contains($name, 'per_page') ? 10 : 1),
            'number' => 0,
            'boolean' => true,
            'array' => [],
            'date' => date('Y-m-d'),
            'email' => 'user@example.com',
            'password' => '********',
            'file' => null,
            default => match (true) {
                str_contains($name, 'password') => '********',
                str_contains($name, 'email') => 'user@example.com',
                str_contains($name, 'phone') || str_contains($name, 'sdt') => '0912345678',
                str_contains($name, 'keyword') => '',
                default => '',
            },
        };
    }

    /**
     * @param  list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>  $params
     * @param  list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>  $nestedHints
     * @return array<string, mixed>
     */
    private function buildExample(array $params, array $nestedHints): array
    {
        $example = [];

        foreach ($params as $param) {
            $name = $param['name'];
            if (str_contains($name, '.')) {
                continue;
            }

            if ($param['type'] === 'array') {
                $item = $this->buildNestedArrayItem($name, $nestedHints);
                $example[$name] = $item !== null ? [$item] : [];
                continue;
            }

            if ($param['type'] === 'file') {
                continue;
            }

            // Chỉ điền sẵn field required vào example body; query thì điền hết
            $example[$name] = $param['example'];
        }

        return $example;
    }

    /**
     * @param  list<array{name: string, required: bool, type: string, rules: list<string>, example: mixed}>  $nestedHints
     * @return array<string, mixed>|null
     */
    private function buildNestedArrayItem(string $parent, array $nestedHints): ?array
    {
        $prefix = $parent.'.*.';
        $item = [];

        foreach ($nestedHints as $hint) {
            if (! str_starts_with($hint['name'], $prefix)) {
                continue;
            }
            $child = substr($hint['name'], strlen($prefix));
            if ($child === '' || str_contains($child, '.')) {
                continue;
            }
            $item[$child] = $hint['example'];
        }

        return $item === [] ? null : $item;
    }

    private function findMethodNode(ReflectionMethod $ref): ?Node\Stmt\ClassMethod
    {
        $file = $ref->getFileName();
        if (! is_string($file) || ! is_file($file)) {
            return null;
        }

        $stmts = $this->parseFile($file);
        if ($stmts === null) {
            return null;
        }

        $finder = new NodeFinder;
        $classNode = $finder->findFirst($stmts, function (Node $node) use ($ref) {
            return $node instanceof Node\Stmt\Class_
                && $node->name
                && $node->name->toString() === $ref->getDeclaringClass()->getShortName();
        });

        if (! $classNode instanceof Node\Stmt\Class_) {
            return null;
        }

        foreach ($classNode->getMethods() as $methodNode) {
            if ($methodNode->name->toString() === $ref->getName()) {
                return $methodNode;
            }
        }

        return null;
    }

    /**
     * @return Node\Stmt[]|null
     */
    private function parseFile(string $file): ?array
    {
        if (isset($this->astCache[$file])) {
            return $this->astCache[$file];
        }

        try {
            $code = file_get_contents($file);
            if ($code === false) {
                return null;
            }

            $parser = (new ParserFactory)->createForNewestSupportedVersion();
            $stmts = $parser->parse($code);
            if ($stmts === null) {
                return null;
            }

            $traverser = new NodeTraverser;
            $traverser->addVisitor(new NameResolver);
            $stmts = $traverser->traverse($stmts);

            $this->astCache[$file] = $stmts;

            return $stmts;
        } catch (Throwable) {
            return null;
        }
    }
}
