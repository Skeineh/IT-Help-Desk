import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Bell, CheckCheck } from 'lucide-react';
import * as notificationService from '../services/notificationService';

function formatTime(value) {
  if (!value) return '';
  return new Date(value).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

export default function NotificationBell() {
  const navigate = useNavigate();
  const dropdownRef = useRef(null);
  const [open, setOpen] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(false);

  const loadCount = () => {
    notificationService.getUnreadCount()
      .then(({ data }) => setUnreadCount(data.unread_count ?? 0))
      .catch(() => {});
  };

  const loadNotifications = () => {
    setLoading(true);
    notificationService.listNotifications()
      .then(({ data }) => setNotifications(data.slice(0, 8)))
      .catch(() => {})
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadCount();
    const id = window.setInterval(loadCount, 30000);
    return () => window.clearInterval(id);
  }, []);

  useEffect(() => {
    if (open) {
      loadNotifications();
    }
  }, [open]);

  useEffect(() => {
    const onClick = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  const handleNotificationClick = async (notification) => {
    if (!notification.is_read) {
      await notificationService.markNotificationRead(notification.id).catch(() => {});
      loadCount();
    }
    setOpen(false);
    if (notification.related_ticket_id) {
      navigate(`/tickets/${notification.related_ticket_id}`);
    } else {
      navigate('/notifications');
    }
  };

  const handleMarkAllRead = async () => {
    await notificationService.markAllNotificationsRead();
    loadCount();
    loadNotifications();
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        title="Notifications"
        className="relative p-2 text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg transition"
      >
        <Bell className="w-4 h-4" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-red-500 text-white text-[11px] font-bold flex items-center justify-center">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-50">
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 className="font-bold text-gray-900 text-sm">Notifications</h3>
            <button
              type="button"
              onClick={handleMarkAllRead}
              className="flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800"
            >
              <CheckCheck className="w-3.5 h-3.5" />
              Mark all
            </button>
          </div>

          {loading ? (
            <div className="flex items-center justify-center py-10">
              <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
            </div>
          ) : notifications.length === 0 ? (
            <div className="py-10 text-center text-sm text-gray-400">No notifications yet.</div>
          ) : (
            <div className="max-h-96 overflow-y-auto divide-y divide-gray-100">
              {notifications.map((notification) => (
                <button
                  type="button"
                  key={notification.id}
                  onClick={() => handleNotificationClick(notification)}
                  className={`w-full text-left px-4 py-3 hover:bg-gray-50 transition ${
                    notification.is_read ? 'bg-white' : 'bg-blue-50/70'
                  }`}
                >
                  <div className="flex items-start justify-between gap-3">
                    <p className="text-sm font-semibold text-gray-900">{notification.title}</p>
                    {!notification.is_read && <span className="w-2 h-2 rounded-full bg-blue-600 mt-1.5 shrink-0" />}
                  </div>
                  <p className="text-xs text-gray-500 mt-1 line-clamp-2">{notification.message}</p>
                  <p className="text-[11px] text-gray-400 mt-1">{formatTime(notification.created_at)}</p>
                </button>
              ))}
            </div>
          )}

          <button
            type="button"
            onClick={() => {
              setOpen(false);
              navigate('/notifications');
            }}
            className="w-full px-4 py-3 text-sm text-blue-600 hover:bg-blue-50 font-medium border-t border-gray-100"
          >
            Open notification center
          </button>
        </div>
      )}
    </div>
  );
}
