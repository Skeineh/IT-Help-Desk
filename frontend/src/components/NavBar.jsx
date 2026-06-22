import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Headphones, LogOut } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import NotificationBell from './NotificationBell';

const ROLE_COLORS = {
  Admin:    'bg-red-500/20 text-red-300 border-red-500/30',
  Manager:  'bg-purple-500/20 text-purple-300 border-purple-500/30',
  Agent:    'bg-blue-500/20 text-blue-300 border-blue-500/30',
  Employee: 'bg-green-500/20 text-green-300 border-green-500/30',
};

export default function NavBar() {
  const { user, logout } = useAuth();
  const navigate         = useNavigate();
  const location         = useLocation();

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const linkCls = (path) => {
    const active = location.pathname === path || location.pathname.startsWith(path + '/');
    return `text-sm font-medium px-3 py-1.5 rounded-lg transition-colors ${
      active
        ? 'bg-white/10 text-white'
        : 'text-slate-400 hover:text-white hover:bg-white/5'
    }`;
  };

  const roleCls = ROLE_COLORS[user?.RoleName] ?? 'bg-gray-500/20 text-gray-300 border-gray-500/30';

  return (
    <header style={{ backgroundColor: '#0d2137' }} className="border-b border-slate-700/60">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-14">

          {/* Left: logo + nav */}
          <div className="flex items-center gap-6">
            <Link to="/tickets" className="flex items-center gap-2 flex-shrink-0">
              <div className="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg p-1.5">
                <Headphones className="w-4 h-4 text-white" />
              </div>
              <span className="text-white font-bold text-sm hidden sm:block">IT Help Desk</span>
            </Link>

            <nav className="flex items-center gap-1">
              <Link to="/tickets"   className={linkCls('/tickets')}>Tickets</Link>
              <Link to="/dashboard" className={linkCls('/dashboard')}>Dashboard</Link>
              {user?.RoleName === 'Admin' && (
                <Link to="/admin/users/new" className={linkCls('/admin/users/new')}>Create User</Link>
              )}
            </nav>
          </div>

          {/* Right: user info + logout */}
          <div className="flex items-center gap-3">
            <NotificationBell />
            <div className="hidden sm:flex items-center gap-2">
              <span className="text-slate-300 text-sm font-medium">{user?.FullName}</span>
              <span className={`text-xs font-semibold px-2 py-0.5 rounded-full border ${roleCls}`}>
                {user?.RoleName}
              </span>
            </div>
            <button
              onClick={handleLogout}
              className="flex items-center gap-1.5 text-sm text-slate-400 hover:text-red-400 bg-white/5 hover:bg-red-500/10 border border-white/10 hover:border-red-500/30 px-3 py-1.5 rounded-lg transition-all"
            >
              <LogOut className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Sign out</span>
            </button>
          </div>

        </div>
      </div>
    </header>
  );
}
