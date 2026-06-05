import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/vi';

dayjs.extend(relativeTime);
dayjs.locale('vi');

export function useChatFormatters() {
  function initials(name) {
    if (!name) return '?';
    return name.split(' ').map((word) => word[0]).join('').toUpperCase().slice(0, 2);
  }

  function relTime(iso) {
    return iso ? dayjs(iso).fromNow(true) : '';
  }

  function formatTime(iso) {
    return iso ? dayjs(iso).format('HH:mm') : '';
  }

  function preview(message) {
    if (!message) return 'Chưa có tin nhắn';
    if (message.type === 'image') return '📷 Hình ảnh';
    if (message.type === 'file') return '📎 Tệp đính kèm';

    const text = message.body ?? '';
    return text.length > 40 ? `${text.slice(0, 40)}...` : text;
  }

  function senderFirstName(fullName) {
    if (!fullName) return '';
    const parts = fullName.trim().split(' ');
    return parts[parts.length - 1] ?? '';
  }

  return { initials, relTime, formatTime, preview, senderFirstName };
}
