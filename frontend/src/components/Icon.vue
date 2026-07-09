<script setup>
// Single source of truth for icons — all Heroicons v1 (https://v1.heroicons.com/).
import {
  ChartBarIcon, FolderIcon, DocumentIcon, DocumentTextIcon, UsersIcon,
  BellIcon, LogoutIcon, MenuIcon, XIcon, PlusIcon, PencilAltIcon,
  DownloadIcon, SearchIcon, ArrowLeftIcon, TrashIcon, PaperClipIcon,
  ArchiveIcon, UserAddIcon, CalendarIcon, ChevronLeftIcon, ChevronRightIcon,
  ChevronDownIcon, UserCircleIcon, LockClosedIcon, CameraIcon, ViewBoardsIcon,
} from '@heroicons/vue/outline'

const map = {
  dashboard: ChartBarIcon,
  projects: FolderIcon,
  files: DocumentIcon,
  users: UsersIcon,
  bell: BellIcon,
  logout: LogoutIcon,
  menu: MenuIcon,
  close: XIcon,
  plus: PlusIcon,
  edit: PencilAltIcon,
  download: DownloadIcon,
  search: SearchIcon,
  back: ArrowLeftIcon,
  trash: TrashIcon,
  paperclip: PaperClipIcon,
  archive: ArchiveIcon,
  'user-add': UserAddIcon,
  doc: DocumentTextIcon,
  calendar: CalendarIcon,
  'chevron-left': ChevronLeftIcon,
  'chevron-right': ChevronRightIcon,
  'chevron-down': ChevronDownIcon,
  user: UserCircleIcon,
  lock: LockClosedIcon,
  camera: CameraIcon,
  board: ViewBoardsIcon,
}

defineProps({ name: { type: String, required: true } })
</script>

<template>
  <component :is="map[name]" />
</template>
