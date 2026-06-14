import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Bell, CheckCheck } from 'lucide-react';
import NavBar from '../components/NavBar';
import * as notificationService from '../services/notificationService';

const PAGE_BG = { backgroundColor: '#0A1929' };

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

export default function NotificationCenter() {
  const navigate = useNavigate();
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const loadNotifications = () => {
    setLoading(true);
    setError('');
    notificationService.listNotifications()
      .then(({ data }) => setNotifications(data))
      .catch(() => setError('Failed to load notifications.'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadNotifications();
  }, []);

  const handleClick = async (notification) => {
    if (!notification.is_read) {
      await notificationService.markNotificationRead(notification.id).catch(() => {});
    }
    if (notification.related_ticket_id) {
      navigate(`/tickets/${notification.related_ticket_id}`);
    } else {
      loadNotifications();
    }
  };

  const markAll = async () => {
    await notificationService.markAllNotificationsRead();
    loadNotifications();
  };

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-4xl mx-auto px-4 sm:px-6 py-8">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold text-white">Notifications</h1>
            <p className="text-slate-400 text-sm mt-1">Review ticket updates and account activity.</p>
          </div>
          <button
            type="button"
            onClick={markAll}
            className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
          >
            <CheckCheck className="w-4 h-4" />
            Mark all as read
          </button>
        </div>

        {error && <div className="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 mb-4 text-sm">{error}</div>}

        <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
          {loading ? (
            <div className="flex items-center justify-center py-24">
              <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
            </div>
          ) : notifications.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-24 text-gray-400">
              <Bell className="w-10 h-10 mb-3" />
              <p className="text-sm">No notifications yet.</p>
            </div>
          ) : (
            <div className="divide-y divide-gray-100">
              {notifications.map((notification) => (
                <button
                  type="button"
                  key={notification.id}
                  onClick={() => handleClick(notification)}
                  className={`w-full text-left px-6 py-5 hover:bg-gray-50 transition ${
                    notification.is_read ? 'bg-white' : 'bg-blue-50/70'
                  }`}
                >
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <div className="flex items-center gap-2">
                        {!notification.is_read && <span className="w-2 h-2 rounded-full bg-blue-600" />}
                        <h2 className="text-sm font-bold text-gray-900">{notification.title}</h2>
                      </div>
                      <p className="text-sm text-gray-600 mt-1">{notification.message}</p>
                      {notification.related_ticket_id && (
                        <p className="text-xs text-blue-600 mt-2">Open related ticket</p>
                      )}
                    </div>
                    <p className="text-xs text-gray-400 whitespace-nowrap">{formatDate(notification.created_at)}</p>
                  </div>
                </button>
              ))}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
