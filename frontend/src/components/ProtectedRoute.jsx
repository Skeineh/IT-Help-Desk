import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function ProtectedRoute({ children, roles = null, allowPasswordChange = false }) {
  const { user, loading } = useAuth();
  const location = useLocation();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-950">
        <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  const mustChangePassword = Boolean(user.MustChangePassword || user.must_change_password);
  if (mustChangePassword && !allowPasswordChange) {
    return <Navigate to="/change-password" replace state={{ from: location.pathname }} />;
  }

  if (!mustChangePassword && allowPasswordChange) {
    return <Navigate to="/dashboard" replace />;
  }

  if (roles && !roles.includes(user.RoleName)) {
    return <Navigate to="/tickets" replace />;
  }

  return children;
}
