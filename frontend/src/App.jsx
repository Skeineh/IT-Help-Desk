import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Login        from './pages/Login';
import ChangePassword from './pages/ChangePassword';
import Dashboard    from './pages/Dashboard';
import TicketList   from './pages/TicketList';
import TicketCreate from './pages/TicketCreate';
import TicketDetails from './pages/TicketDetails';
import TicketEdit   from './pages/TicketEdit';
import AdminCreateUser from './pages/AdminCreateUser';
import NotificationCenter from './pages/NotificationCenter';

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/change-password" element={<ProtectedRoute allowPasswordChange><ChangePassword /></ProtectedRoute>} />

          <Route path="/tickets" element={<ProtectedRoute><TicketList /></ProtectedRoute>} />
          <Route path="/tickets/new" element={<ProtectedRoute><TicketCreate /></ProtectedRoute>} />
          <Route path="/tickets/:id" element={<ProtectedRoute><TicketDetails /></ProtectedRoute>} />
          <Route path="/tickets/:id/edit" element={<ProtectedRoute><TicketEdit /></ProtectedRoute>} />

          <Route path="/dashboard" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />
          <Route path="/notifications" element={<ProtectedRoute><NotificationCenter /></ProtectedRoute>} />
          <Route path="/admin/users/new" element={<ProtectedRoute roles={['Admin']}><AdminCreateUser /></ProtectedRoute>} />

          {/* Default redirect */}
          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
