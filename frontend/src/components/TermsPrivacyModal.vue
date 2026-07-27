<script setup>
/**
 * Modal Điều khoản sử dụng & Chính sách bảo mật.
 * Dùng tab để chuyển nội dung; mở bằng v-model + prop `initialTab`.
 */
import { ref, watch } from 'vue'
import CustomDialog from '@/components/element/CustomDialog.vue'

defineOptions({ name: 'TermsPrivacyModal' })

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  /** Tab mở mặc định: 'terms' | 'privacy' */
  initialTab: {
    type: String,
    default: 'terms',
    validator: (v) => ['terms', 'privacy'].includes(v),
  },
})

const activeTab = ref(props.initialTab)

watch(visible, (open) => {
  if (open) activeTab.value = props.initialTab
})
</script>

<template>
  <CustomDialog
    v-model="visible"
    :title="activeTab === 'privacy' ? 'Chính sách bảo mật' : 'Điều khoản sử dụng'"
    :width="720"
  >
    <el-tabs v-model="activeTab" class="terms-tabs">
      <el-tab-pane label="Điều khoản sử dụng" name="terms">
        <div class="terms-body">
          <p class="terms-updated">Cập nhật lần cuối: 01/01/2026</p>

          <h4>1. Chấp nhận điều khoản</h4>
          <p>
            Khi truy cập và sử dụng hệ thống Pandio, bạn đồng ý tuân thủ các điều khoản
            được nêu trong tài liệu này. Nếu không đồng ý, vui lòng ngừng sử dụng dịch vụ.
          </p>

          <h4>2. Tài khoản người dùng</h4>
          <p>
            Tài khoản được cấp bởi quản trị viên studio hoặc tổ chức của bạn. Bạn có trách nhiệm
            bảo mật thông tin đăng nhập và mọi hoạt động phát sinh từ tài khoản của mình.
            Không được chia sẻ mật khẩu hoặc chuyển nhượng quyền truy cập cho bên thứ ba
            khi chưa được phép.
          </p>

          <h4>3. Phạm vi sử dụng</h4>
          <p>
            Pandio cung cấp công cụ quản lý vận hành studio (khách hàng, lịch, hợp đồng,
            nhân sự, tài chính…). Bạn chỉ được sử dụng hệ thống cho mục đích hợp pháp và
            phù hợp với quyền hạn được phân công trong tổ chức.
          </p>

          <h4>4. Dữ liệu và nội dung</h4>
          <p>
            Dữ liệu bạn nhập vào hệ thống thuộc quyền sở hữu của studio/tổ chức tương ứng.
            Bạn cam kết không tải lên nội dung vi phạm pháp luật, xâm phạm quyền sở hữu trí tuệ
            hoặc quyền riêng tư của người khác.
          </p>

          <h4>5. Giới hạn trách nhiệm</h4>
          <p>
            Pandio nỗ lực duy trì dịch vụ ổn định nhưng không đảm bảo hoạt động không gián đoạn.
            Trong phạm vi pháp luật cho phép, chúng tôi không chịu trách nhiệm về thiệt hại
            gián tiếp phát sinh từ việc sử dụng hoặc không thể sử dụng hệ thống.
          </p>

          <h4>6. Thay đổi điều khoản</h4>
          <p>
            Điều khoản có thể được cập nhật theo thời gian. Phiên bản mới sẽ được công bố
            trên hệ thống. Việc tiếp tục sử dụng sau khi cập nhật đồng nghĩa với việc bạn
            chấp nhận điều khoản mới.
          </p>

          <h4>7. Liên hệ</h4>
          <p>
            Mọi thắc mắc liên quan đến điều khoản sử dụng, vui lòng liên hệ bộ phận hỗ trợ
            Pandio hoặc quản trị viên studio của bạn.
          </p>
        </div>
      </el-tab-pane>

      <el-tab-pane label="Chính sách bảo mật" name="privacy">
        <div class="terms-body">
          <p class="terms-updated">Cập nhật lần cuối: 01/01/2026</p>

          <h4>1. Mục đích thu thập</h4>
          <p>
            Chúng tôi thu thập và xử lý thông tin cá nhân nhằm cung cấp, vận hành và cải thiện
            dịch vụ Pandio; xác thực người dùng; hỗ trợ kỹ thuật; và tuân thủ nghĩa vụ pháp lý
            khi có yêu cầu.
          </p>

          <h4>2. Loại dữ liệu</h4>
          <p>Tùy theo chức năng được sử dụng, dữ liệu có thể bao gồm:</p>
          <ul>
            <li>Thông tin tài khoản: họ tên, email, số điện thoại, vai trò</li>
            <li>Dữ liệu vận hành: khách hàng, hợp đồng, lịch làm việc, chấm công…</li>
            <li>Dữ liệu kỹ thuật: nhật ký truy cập, địa chỉ IP, thiết bị, trình duyệt</li>
          </ul>

          <h4>3. Cách thức bảo vệ</h4>
          <p>
            Dữ liệu được bảo vệ bằng các biện pháp kỹ thuật và tổ chức phù hợp, bao gồm
            xác thực đăng nhập, phân quyền truy cập, truyền tải mã hóa (HTTPS) và kiểm soát
            truy cập nội bộ. Bạn nên sử dụng mật khẩu mạnh và không chia sẻ phiên đăng nhập.
          </p>

          <h4>4. Chia sẻ dữ liệu</h4>
          <p>
            Chúng tôi không bán thông tin cá nhân. Dữ liệu chỉ được chia sẻ khi cần thiết
            để vận hành dịch vụ (ví dụ nhà cung cấp hạ tầng), khi có yêu cầu pháp lý hợp lệ,
            hoặc khi bạn/tổ chức của bạn ủy quyền.
          </p>

          <h4>5. Lưu trữ và thời hạn</h4>
          <p>
            Dữ liệu được lưu trong thời gian cần thiết để cung cấp dịch vụ và đáp ứng yêu cầu
            của tổ chức sử dụng. Khi tài khoản hoặc hợp đồng dịch vụ chấm dứt, dữ liệu có thể
            được xóa hoặc ẩn danh theo chính sách lưu trữ của tổ chức và quy định pháp luật.
          </p>

          <h4>6. Quyền của người dùng</h4>
          <p>
            Trong phạm vi pháp luật và chính sách tổ chức, bạn có thể yêu cầu xem, cập nhật
            hoặc chỉnh sửa thông tin cá nhân; yêu cầu hạn chế xử lý trong một số trường hợp;
            và liên hệ quản trị viên studio để được hỗ trợ các yêu cầu liên quan đến dữ liệu.
          </p>

          <h4>7. Cookie và lưu trữ cục bộ</h4>
          <p>
            Hệ thống có thể sử dụng cookie hoặc bộ nhớ trình duyệt (localStorage) để duy trì
            phiên đăng nhập, ghi nhớ tùy chọn giao diện và cải thiện trải nghiệm người dùng.
          </p>

          <h4>8. Liên hệ bảo mật</h4>
          <p>
            Nếu có câu hỏi về chính sách bảo mật hoặc nghi ngờ rò rỉ dữ liệu, vui lòng liên hệ
            bộ phận hỗ trợ Pandio hoặc quản trị viên studio ngay lập tức.
          </p>
        </div>
      </el-tab-pane>
    </el-tabs>

    <template #footer>
      <el-button type="primary" @click="visible = false">Đã hiểu</el-button>
    </template>
  </CustomDialog>
</template>

<style scoped>
.terms-tabs :deep(.el-tabs__header) {
  margin-bottom: 12px;
}

.terms-body {
  font-size: 14px;
  line-height: 1.65;
  color: var(--el-text-color-regular);
  max-height: min(52vh, 480px);
  overflow-y: auto;
  padding-right: 4px;
}

.terms-updated {
  margin: 0 0 16px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.terms-body h4 {
  margin: 18px 0 8px;
  font-size: 15px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.terms-body h4:first-of-type {
  margin-top: 0;
}

.terms-body p {
  margin: 0 0 8px;
}

.terms-body ul {
  margin: 0 0 8px;
  padding-left: 20px;
}

.terms-body li {
  margin-bottom: 4px;
}
</style>
