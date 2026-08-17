import zh_CN from "@/lang/zh_CN";
import en_US from "@/lang/en_US";
import ko from "@/lang/ko";
import { createI18n } from "vue-i18n";

export const locales = [
	{ name: 'zh_CN', locale: zh_CN, merge: true },
	{ name: 'en_US', locale: en_US, merge: true },
	{ name: 'ko', locale: ko, merge: true }
]

const storage = localStorage.getItem("app")
const cache = storage ? JSON.parse(storage) : { locale: 'zh_CN' }

export const t = createI18n({
	locale:cache.locale || "zh_CN", // 语言标识
	globalInjection: true,
	legacy: false,
	messages:{
		zh_CN,
		en_US,
		ko
	}
})
