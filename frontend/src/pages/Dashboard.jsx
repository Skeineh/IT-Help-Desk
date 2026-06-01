import { useNavigate } from 'react-router-dom';
import { LogOut, Headphones, User, Shield } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

const ROLE_COLORS = {
  Admin:    'bg-red-100 text-red-700 border-red-200',
  Manager:  'bg-purple-100 text-purple-700 border-purple-200',
  Agent:    'bg-blue-100 text-blue-700 border-blue-200',
  Employee: 'bg-green-100 text-green-700 border-green-200',
};

export default function Dashboard() {
  const { user, logout } = useAuth();
  const navigate         = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const roleColor = ROLE_COLORS[user?.RoleName] ?? 'bg-gray-100 text-gray-700 border-gray-200';

  return (
    <div className="min-h-screen bg-gray-50">

      {/* Top nav */}
      <header className="bg-white border-b border-gray-200 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center gap-3">
              <div className="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl p-2">
                <Headphones className="w-5 h-5 text-white" />
              </div>
              <span className="font-bold text-gray-900 text-lg">IT Help Desk</span>
            </div>

            <div className="flex items-center gap-4">
              <span className="hidden sm:block text-sm text-gray-500">{user?.Email}</span>
              <button
                onClick={handleLogout}
                className="flex items-center gap-2 text-sm text-gray-600 hover:text-red-600 bg-gray-100 hover:bg-red-50 border border-gray-200 hover:border-red-200 px-3 py-1.5 rounded-lg transition-all duration-150"
              >
                <LogOut className="w-4 h-4" />
                Sign out
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {/* Welcome card */}
        <div className="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-8 text-white shadow-xl shadow-blue-200 mb-8">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-blue-200 text-sm font-medium mb-1">Welcome back</p>
              <h1 className="text-3xl font-bold mb-3">{user?.FullName}</h1>
              <span className={`inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full border ${roleColor}`}>
                <Shield className="w-3 h-3" />
                Role: {user?.RoleName}
              </span>
              {user?.Department && (
                <p className="text-blue-200 text-sm mt-3">
                  Department: <span className="text-white font-medium">{user.Department}</span>
                </p>
              )}
            </div>
            <div className="bg-white/10 rounded-2xl p-4 hidden sm:block">
              <User className="w-10 h-10 text-white/80" />
            </div>
          </div>
        </div>

        {/* Placeholder panels */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {[
            { label: 'Open Tickets',    value: '—', color: 'text-blue-600' },
            { label: 'In Progress',     value: '—', color: 'text-yellow-600' },
            { label: 'Resolved Today',  value: '—', color: 'text-green-600' },
          ].map((card) => (
            <div key={card.label} className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
              <p className="text-sm text-gray-500">{card.label}</p>
              <p className={`text-3xl font-bold mt-1 ${card.color}`}>{card.value}</p>
            </div>
          ))}
        </div>

        <p className="text-center text-sm text-gray-400 mt-12">
          Dashboard content coming in the next sprint.
        </p>
      </main>
    </div>
  );
}
