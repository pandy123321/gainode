<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue'
import { t } from '../i18n'
import { ProjectApi } from '../api/services'

interface Country {
  flag: string
  name: string
  code: string
}

const props = defineProps<{
  modelValue: { flag: string; code: string }
}>()

const emit = defineEmits<{
  'update:modelValue': [value: { flag: string; code: string }]
}>()

const show = ref(false)
const query = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
const countries = ref<Country[]>([])
const countriesLoaded = ref(false)

const fallbackCountries: Country[] = [
  { flag: '🇨🇳', name: 'China', code: '+86' },
  { flag: '🇭🇰', name: 'Hong Kong', code: '+852' },
  { flag: '🇲🇴', name: 'Macao', code: '+853' },
  { flag: '🇹🇼', name: 'Taiwan', code: '+886' },
  { flag: '🇺🇸', name: 'United States', code: '+1' },
  { flag: '🇬🇧', name: 'United Kingdom', code: '+44' },
  { flag: '🇯🇵', name: 'Japan', code: '+81' },
  { flag: '🇰🇷', name: 'South Korea', code: '+82' },
  { flag: '🇸🇬', name: 'Singapore', code: '+65' },
  { flag: '🇲🇾', name: 'Malaysia', code: '+60' },
  { flag: '🇹🇭', name: 'Thailand', code: '+66' },
  { flag: '🇻🇳', name: 'Vietnam', code: '+84' },
  { flag: '🇵🇭', name: 'Philippines', code: '+63' },
  { flag: '🇮🇩', name: 'Indonesia', code: '+62' },
  { flag: '🇮🇳', name: 'India', code: '+91' },
  { flag: '🇦🇺', name: 'Australia', code: '+61' },
  { flag: '🇳🇿', name: 'New Zealand', code: '+64' },
  { flag: '🇨🇦', name: 'Canada', code: '+1' },
  { flag: '🇩🇪', name: 'Germany', code: '+49' },
  { flag: '🇫🇷', name: 'France', code: '+33' },
  { flag: '🇮🇹', name: 'Italy', code: '+39' },
  { flag: '🇪🇸', name: 'Spain', code: '+34' },
  { flag: '🇷🇺', name: 'Russia', code: '+7' },
  { flag: '🇧🇷', name: 'Brazil', code: '+55' },
  { flag: '🇦🇷', name: 'Argentina', code: '+54' },
  { flag: '🇲🇽', name: 'Mexico', code: '+52' },
  { flag: '🇦🇪', name: 'UAE', code: '+971' },
  { flag: '🇸🇦', name: 'Saudi Arabia', code: '+966' },
  { flag: '🇹🇷', name: 'Turkey', code: '+90' },
  { flag: '🇳🇬', name: 'Nigeria', code: '+234' },
  { flag: '🇿🇦', name: 'South Africa', code: '+27' },
  { flag: '🇪🇬', name: 'Egypt', code: '+20' },
  { flag: '🇵🇰', name: 'Pakistan', code: '+92' },
  { flag: '🇧🇩', name: 'Bangladesh', code: '+880' },
  { flag: '🇳🇱', name: 'Netherlands', code: '+31' },
  { flag: '🇨🇭', name: 'Switzerland', code: '+41' },
  { flag: '🇸🇪', name: 'Sweden', code: '+46' },
  { flag: '🇧🇪', name: 'Belgium', code: '+32' },
  { flag: '🇵🇹', name: 'Portugal', code: '+351' },
  { flag: '🇵🇱', name: 'Poland', code: '+48' },
  { flag: '🇺🇦', name: 'Ukraine', code: '+380' },
  { flag: '🇮🇱', name: 'Israel', code: '+972' },
  { flag: '🇰🇿', name: 'Kazakhstan', code: '+7' },
  { flag: '🇶🇦', name: 'Qatar', code: '+974' },
  { flag: '🇰🇼', name: 'Kuwait', code: '+965' },
]

onMounted(async () => {
  const res = await ProjectApi.getCountryList()
  if (res.code === 0 && res.data) {
    const list: any[] = Array.isArray(res.data) ? res.data : (res.data.data || [])
    if (list.length) {
      countries.value = list.map((c: any) => ({
        flag: c.flag || '',
        name: c.name || '',
        code: '+' + (c.dial || ''),
      }))
      countriesLoaded.value = true
    }
  }
  if (!countries.value.length) {
    countries.value = fallbackCountries
  }
})

function getList(): Country[] {
  return countries.value.length ? countries.value : fallbackCountries
}

const filtered = computed(() => {
  const list = getList()
  if (!query.value.trim()) return list
  const q = query.value.toLowerCase()
  return list.filter(
    (c) => c.name.toLowerCase().includes(q) || c.code.includes(q)
  )
})

function select(c: Country) {
  emit('update:modelValue', { flag: c.flag, code: c.code })
  show.value = false
  query.value = ''
}

function open() {
  show.value = true
  query.value = ''
  if (!countries.value.length) {
    // trigger lazy load
    ProjectApi.getCountryList().then((res: any) => {
      if (res.code === 0 && res.data) {
        const list: any[] = Array.isArray(res.data) ? res.data : (res.data.data || [])
        if (list.length) {
          countries.value = list.map((c: any) => ({
            flag: c.flag || '',
            name: c.name || '',
            code: '+' + (c.dial || ''),
          }))
        }
      }
      if (!countries.value.length) countries.value = fallbackCountries
    })
  }
  nextTick(() => searchInput.value?.focus())
}
</script>

<template>
  <div class="country-picker">
    <div class="country-trigger" @click="open">
      <span class="flag">{{ modelValue.flag }}</span>
      <span class="code">{{ modelValue.code }}</span>
    
    </div>

    <Teleport to="body">
      <div v-if="show" class="picker-overlay" @click.self="show = false">
        <div class="picker-sheet">
          <div class="picker-header">
            <div class="search-box">
              <svg viewBox="0 0 24 24" fill="none" width="18" height="18" class="search-icon"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
              <input v-model="query" type="text" :placeholder="t('search_country_hint')" ref="searchInput" />
              <button v-if="query" class="clear-btn" @click="query = ''">
                <svg viewBox="0 0 20 20" fill="none" width="16" height="16"><path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
              </button>
            </div>
            <button class="close-btn" @click="show = false">
              <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
          </div>
          <div class="picker-list">
            <button
              v-for="c in filtered"
              :key="c.code + c.flag"
              class="country-item"
              :class="{ selected: c.code === modelValue.code && c.flag === modelValue.flag }"
              @click="select(c)"
            >
              <span class="item-flag">{{ c.flag }}</span>
              <span class="item-name">{{ c.name }}</span>
              <span class="item-code">{{ c.code }}</span>
              <svg v-if="c.code === modelValue.code && c.flag === modelValue.flag" viewBox="0 0 20 20" fill="none" width="16" height="16"><path d="M4 10l4 4 8-8" stroke="#3DDC97" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped lang="scss">
$elevated: #12181F;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;

.country-trigger {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  user-select: none;

  .flag { font-size: 20px; }
  .code { font-size: 15px; font-weight: 500; color: white; margin-right: 8px; }
  svg { color: $muted; }
}

.picker-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: flex-end;
  background: rgba(0, 0, 0, 0.5);
}

.picker-sheet {
  width: 100%;
  max-height: 70vh;
  background: $elevated;
  border-radius: 20px 20px 0 0;
  display: flex;
  flex-direction: column;
}

.picker-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-bottom: 1px solid $border;

  .close-btn {
    background: none;
    border: none;
    color: $muted;
    cursor: pointer;
    flex-shrink: 0;
    padding: 4px;
  }
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  height: 38px;
  padding: 0 10px;
  background: $border;
  border-radius: 10px;

  .search-icon { color: $muted; flex-shrink: 0; width: 16px; height: 16px; }

  input {
    flex: 1;
    background: none;
    border: none;
    color: white;
    font-size: 14px;
    outline: none;

    &::placeholder { color: #4A5568; }
  }

  .clear-btn {
    background: none;
    border: none;
    color: $muted;
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
  }
}

.picker-list {
  flex: 1;
  overflow-y: auto;
  padding: 4px 0;

  .country-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;

    &:active {
      background: rgba($border, 0.5);
    }

    &.selected {
      background: $border;
    }

    .item-flag { font-size: 20px; width: 28px; text-align: center; }
    .item-name { flex: 1; font-size: 14px; color: white; }
    .item-code { font-size: 14px; color: $muted; font-weight: 500; }
  }
}
</style>
