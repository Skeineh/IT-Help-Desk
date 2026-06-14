import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { AlertCircle, KeyRound } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

const PAGE_BG = { backgroundColor: '#0A1929' };

export default function ChangePassword() {
  const navigate = useNavigate();
  const { user, changePassword, logout } = useAuth();

  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);

    try {
      await changePassword(currentPassword, newPassword, confirmPassword);
      navigate('/dashboard', { replace: true });
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors ?? { general: [err.response.data.message] });
      } else {
        setErrors({ general: [err.response?.data?.message || 'Failed to change password.'] });
      }
    } finally {
      setLoading(false);
    }
  };

  const fieldCls = 'w-full px-3 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition';
  const labelCls = 'block text-xs font-bold text-gray-700 mb-1.5';
  const generalError = errors.general?.[0];

  return (
    <div className="min-h-screen flex items-center justify-center p-4" style={PAGE_BG}>
      <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center">
            <KeyRound className="w-5 h-5 text-white" />
          </div>
          <div>
            <h1 className="text-xl font-bold text-gray-900">Change Password</h1>
            <p className="text-sm text-gray-500">{user?.FullName}</p>
          </div>
        </div>

        <p className="text-sm text-gray-600 mb-6">
          Your account was created by an administrator. Please set a new password before continuing.
        </p>

        {generalError && (
          <div className="flex items-start gap-2 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
            <AlertCircle className="w-4 h-4 mt-0.5 shrink-0" />
            <span>{generalError}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className={labelCls}>Current Password</label>
            <input
              type="password"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              className={fieldCls}
              required
            />
            {errors.current_password && <p className="text-red-500 text-xs mt-1">{errors.current_password[0]}</p>}
          </div>

          <div>
            <label className={labelCls}>New Password</label>
            <input
              type="password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              className={fieldCls}
              minLength={8}
              required
            />
            {errors.new_password && <p className="text-red-500 text-xs mt-1">{errors.new_password[0]}</p>}
          </div>

          <div>
            <label className={labelCls}>Confirm New Password</label>
            <input
              type="password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              className={fieldCls}
              minLength={8}
              required
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-lg text-sm transition-colors flex items-center justify-center gap-2"
          >
            {loading ? (
              <>
                <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                Saving...
              </>
            ) : 'Save Password'}
          </button>
        </form>

        <button
          type="button"
          onClick={logout}
          className="w-full mt-4 text-sm text-gray-500 hover:text-gray-800 transition"
        >
          Sign out instead
        </button>
      </div>
    </div>
  );
}
