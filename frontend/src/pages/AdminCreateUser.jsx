import { useEffect, useState } from 'react';
import { AlertCircle, CheckCircle2, UserPlus } from 'lucide-react';
import NavBar from '../components/NavBar';
import * as adminService from '../services/adminService';

const PAGE_BG = { backgroundColor: '#0A1929' };

export default function AdminCreateUser() {
  const [roles, setRoles] = useState([]);
  const [form, setForm] = useState({
    FullName: '',
    Email: '',
    Password: '',
    RoleNumber: '',
    Department: '',
    PhoneNumber: '',
  });
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingRoles, setLoadingRoles] = useState(true);

  useEffect(() => {
    adminService.getRoles()
      .then(({ data }) => setRoles(data))
      .catch(() => setErrors({ general: ['Failed to load roles.'] }))
      .finally(() => setLoadingRoles(false));
  }, []);

  const updateField = (field) => (e) => {
    setForm((current) => ({ ...current, [field]: e.target.value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setMessage('');
    setLoading(true);

    try {
      await adminService.createUser({
        ...form,
        RoleNumber: Number(form.RoleNumber),
      });
      setMessage('User created successfully. They must change password on first login.');
      setForm({
        FullName: '',
        Email: '',
        Password: '',
        RoleNumber: '',
        Department: '',
        PhoneNumber: '',
      });
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors ?? {});
      } else {
        setErrors({ general: [err.response?.data?.message || 'Failed to create user.'] });
      }
    } finally {
      setLoading(false);
    }
  };

  const fieldCls = 'w-full px-3 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition';
  const labelCls = 'block text-xs font-bold text-gray-700 mb-1.5';

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-2xl mx-auto px-4 sm:px-6 py-8">
        <div className="bg-white rounded-2xl shadow-2xl p-8">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center">
              <UserPlus className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Create User</h1>
              <p className="text-sm text-gray-500">Admin-created accounts receive temporary credentials.</p>
            </div>
          </div>

          {errors.general && (
            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
              <AlertCircle className="w-4 h-4 mt-0.5 shrink-0" />
              <span>{errors.general[0]}</span>
            </div>
          )}

          {message && (
            <div className="flex items-start gap-2.5 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-5 text-sm">
              <CheckCircle2 className="w-4 h-4 mt-0.5 shrink-0" />
              <span>{message}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className={labelCls}>Name</label>
              <input value={form.FullName} onChange={updateField('FullName')} className={fieldCls} required />
              {errors.FullName && <p className="text-red-500 text-xs mt-1">{errors.FullName[0]}</p>}
            </div>

            <div>
              <label className={labelCls}>Email</label>
              <input type="email" value={form.Email} onChange={updateField('Email')} className={fieldCls} required />
              {errors.Email && <p className="text-red-500 text-xs mt-1">{errors.Email[0]}</p>}
            </div>

            <div>
              <label className={labelCls}>Temporary Password</label>
              <input type="password" value={form.Password} onChange={updateField('Password')} className={fieldCls} minLength={8} required />
              {errors.Password && <p className="text-red-500 text-xs mt-1">{errors.Password[0]}</p>}
            </div>

            <div>
              <label className={labelCls}>Role</label>
              <select value={form.RoleNumber} onChange={updateField('RoleNumber')} className={fieldCls} required disabled={loadingRoles}>
                <option value="">{loadingRoles ? 'Loading roles...' : 'Select role'}</option>
                {roles.map((role) => (
                  <option key={role.RoleNumber} value={role.RoleNumber}>{role.RoleName}</option>
                ))}
              </select>
              {errors.RoleNumber && <p className="text-red-500 text-xs mt-1">{errors.RoleNumber[0]}</p>}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className={labelCls}>Department</label>
                <input value={form.Department} onChange={updateField('Department')} className={fieldCls} />
                {errors.Department && <p className="text-red-500 text-xs mt-1">{errors.Department[0]}</p>}
              </div>
              <div>
                <label className={labelCls}>Phone</label>
                <input value={form.PhoneNumber} onChange={updateField('PhoneNumber')} className={fieldCls} />
                {errors.PhoneNumber && <p className="text-red-500 text-xs mt-1">{errors.PhoneNumber[0]}</p>}
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-lg text-sm transition-colors flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Creating...
                </>
              ) : 'Create User'}
            </button>
          </form>
        </div>
      </main>
    </div>
  );
}
