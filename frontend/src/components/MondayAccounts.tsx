import React, { useState } from 'react';
import { mondayApi } from '../services/api';

interface MondayAccount {
  id: number;
  name: string;
  monday_username: string;
  monday_email: string;
  is_active: boolean;
  boards?: any[];
}

interface Props {
  accounts: MondayAccount[];
  onUpdate: () => void;
}

const MondayAccounts: React.FC<Props> = ({ accounts, onUpdate }) => {
  const [showAddForm, setShowAddForm] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    access_token: '',
    account_type: 'personal'
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [testingConnection, setTestingConnection] = useState<number | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError('');

    try {
      await mondayApi.createAccount(formData);
      setFormData({ name: '', access_token: '', account_type: 'personal' });
      setShowAddForm(false);
      onUpdate();
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to add account');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Are you sure you want to delete this Monday account?')) return;

    setIsSubmitting(true);
    setError('');

    try {
      await mondayApi.deleteAccount(id);
      onUpdate();
      alert('Monday account deleted successfully!');
    } catch (err: any) {
      console.error('Failed to delete account:', err);
      const errorMessage = err.response?.data?.message || err.response?.data?.error || 'Failed to delete account';
      setError(errorMessage);
      alert(`Error: ${errorMessage}`);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleTestConnection = async (id: number) => {
    setTestingConnection(id);
    try {
      const response = await mondayApi.testConnection(id);
      if (response.data.success) {
        alert('Connection successful!');
      } else {
        alert('Connection failed: ' + response.data.error);
      }
    } catch (err: any) {
      alert('Connection failed: ' + (err.response?.data?.error || 'Unknown error'));
    } finally {
      setTestingConnection(null);
    }
  };

  const styles = {
    container: {
      backgroundColor: 'white',
      borderRadius: '8px',
      padding: '1.5rem',
      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
    },
    header: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: '1.5rem',
    },
    title: {
      fontSize: '1.25rem',
      fontWeight: 'bold',
      color: '#333',
      display: 'flex',
      alignItems: 'center',
      gap: '0.5rem',
    },
    mondayLogo: {
      width: '24px',
      height: '24px',
      backgroundColor: '#00D2FF',
      borderRadius: '4px',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      color: 'white',
      fontSize: '12px',
      fontWeight: 'bold',
    },
    addButton: {
      padding: '0.5rem 1rem',
      backgroundColor: '#00D2FF',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    form: {
      backgroundColor: '#f8f9fa',
      padding: '1rem',
      borderRadius: '8px',
      marginBottom: '1rem',
    },
    formGroup: {
      marginBottom: '1rem',
    },
    label: {
      display: 'block',
      marginBottom: '0.25rem',
      fontSize: '0.9rem',
      fontWeight: '500',
    },
    input: {
      width: '100%',
      padding: '0.5rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
      fontSize: '0.9rem',
    },
    select: {
      width: '100%',
      padding: '0.5rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
      fontSize: '0.9rem',
    },
    formActions: {
      display: 'flex',
      gap: '0.5rem',
    },
    submitButton: {
      padding: '0.5rem 1rem',
      backgroundColor: '#00D2FF',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    cancelButton: {
      padding: '0.5rem 1rem',
      backgroundColor: '#6c757d',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    accountList: {
      display: 'flex',
      flexDirection: 'column' as const,
      gap: '1rem',
    },
    accountCard: {
      border: '1px solid #e0e0e0',
      borderRadius: '8px',
      padding: '1rem',
    },
    accountHeader: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      marginBottom: '0.5rem',
    },
    accountName: {
      fontSize: '1.1rem',
      fontWeight: 'bold',
      marginBottom: '0.25rem',
    },
    accountInfo: {
      fontSize: '0.9rem',
      color: '#666',
    },
    accountActions: {
      display: 'flex',
      gap: '0.5rem',
    },
    actionButton: {
      padding: '0.25rem 0.75rem',
      fontSize: '0.8rem',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    testButton: {
      backgroundColor: '#28a745',
      color: 'white',
    },
    deleteButton: {
      backgroundColor: '#dc3545',
      color: 'white',
    },
    statusBadge: {
      padding: '0.25rem 0.5rem',
      borderRadius: '12px',
      fontSize: '0.75rem',
      fontWeight: 'bold',
    },
    activeBadge: {
      backgroundColor: '#d4edda',
      color: '#155724',
    },
    inactiveBadge: {
      backgroundColor: '#f8d7da',
      color: '#721c24',
    },
    error: {
      color: '#dc3545',
      backgroundColor: '#f8d7da',
      border: '1px solid #f5c6cb',
      borderRadius: '4px',
      padding: '0.75rem',
      marginBottom: '1rem',
    },
    empty: {
      textAlign: 'center' as const,
      padding: '2rem',
      color: '#666',
    },
  };

  return (
    <div style={styles.container}>
      <div style={styles.header}>
        <h2 style={styles.title}>
          <div style={styles.mondayLogo}>M</div>
          Monday.com Accounts
        </h2>
        <button style={styles.addButton} onClick={() => setShowAddForm(!showAddForm)}>
          {showAddForm ? 'Cancel' : 'Add Account'}
        </button>
      </div>

      {showAddForm && (
        <form style={styles.form} onSubmit={handleSubmit}>
          {error && <div style={styles.error}>{error}</div>}
          
          <div style={styles.formGroup}>
            <label style={styles.label}>Account Name</label>
            <input
              type="text"
              style={styles.input}
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              placeholder="e.g., My Monday Workspace"
              required
            />
          </div>

          <div style={styles.formGroup}>
            <label style={styles.label}>Access Token</label>
            <input
              type="password"
              style={styles.input}
              value={formData.access_token}
              onChange={(e) => setFormData({ ...formData, access_token: e.target.value })}
              placeholder="Your Monday.com API token"
              required
            />
            <small style={{ color: '#666', fontSize: '0.8rem' }}>
              Get your API token from Monday.com Developer settings
            </small>
          </div>

          <div style={styles.formGroup}>
            <label style={styles.label}>Account Type</label>
            <select
              style={styles.select}
              value={formData.account_type}
              onChange={(e) => setFormData({ ...formData, account_type: e.target.value })}
            >
              <option value="personal">Personal</option>
              <option value="oauth">OAuth</option>
            </select>
          </div>

          <div style={styles.formActions}>
            <button type="submit" style={styles.submitButton} disabled={isSubmitting}>
              {isSubmitting ? 'Adding...' : 'Add Account'}
            </button>
            <button type="button" style={styles.cancelButton} onClick={() => setShowAddForm(false)}>
              Cancel
            </button>
          </div>
        </form>
      )}

      <div style={styles.accountList}>
        {accounts.length === 0 ? (
          <div style={styles.empty}>
            No Monday.com accounts added yet. Click "Add Account" to get started.
          </div>
        ) : (
          accounts.map((account) => (
            <div key={account.id} style={styles.accountCard}>
              <div style={styles.accountHeader}>
                <div>
                  <div style={styles.accountName}>{account.name}</div>
                  <div style={styles.accountInfo}>
                    {account.monday_username} ({account.monday_email})
                  </div>
                  <div style={styles.accountInfo}>
                    Boards: {account.boards?.length || 0}
                  </div>
                </div>
                <div
                  style={{
                    ...styles.statusBadge,
                    ...(account.is_active ? styles.activeBadge : styles.inactiveBadge),
                  }}
                >
                  {account.is_active ? 'Active' : 'Inactive'}
                </div>
              </div>

              <div style={styles.accountActions}>
                <button
                  style={{ ...styles.actionButton, ...styles.testButton }}
                  onClick={() => handleTestConnection(account.id)}
                  disabled={testingConnection === account.id}
                >
                  {testingConnection === account.id ? 'Testing...' : 'Test Connection'}
                </button>
                <button
                  style={{ ...styles.actionButton, ...styles.deleteButton }}
                  onClick={() => handleDelete(account.id)}
                >
                  Delete
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export default MondayAccounts;