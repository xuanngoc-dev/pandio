import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import vi from 'element-plus/es/locale/lang/vi'
import dayjs from 'dayjs'
import 'dayjs/locale/vi'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/dark/css-vars.css'

import App from './App.vue'
import router from './router'
import './styles/index.scss'
import './styles/page-list.scss'

// Calendar / date picker: tuần bắt đầu từ Thứ 2 (locale vi)
dayjs.locale('vi')

const app = createApp(App)

// Đăng ký toàn bộ icon Element Plus (global)
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

app.use(createPinia())
app.use(router)
app.use(ElementPlus, { locale: vi })

// Dark mode mặc định theo .env
if (import.meta.env.VITE_DARK_MODE === 'true') {
  document.documentElement.classList.add('dark')
}

app.mount('#app')
