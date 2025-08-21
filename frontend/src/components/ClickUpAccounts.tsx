import React, { useState } from 'react';
import { clickUpApi } from '../services/api';

interface ClickUpAccount {
  id: number;
  name: string;
  clickup_username: string;
  clickup_email: string;
  is_active: boolean;
  created_at: string;
}

interface Props {
  accounts: ClickUpAccount[];
  onUpdate: () => void;
}

const ClickUpAccounts: React.FC<Props> = ({ accounts, onUpdate }) => {
  const [showAddForm, setShowAddForm] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    access_token: '',
    account_type: 'personal',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await clickUpApi.createAccount(formData);
      setFormData({ name: '', access_token: '', account_type: 'personal' });
      setShowAddForm(false);
      onUpdate();
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to add account');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Are you sure you want to delete this account?')) return;
    
    setLoading(true);
    setError('');
    
    try {
      await clickUpApi.deleteAccount(id);
      onUpdate();
      alert('Account deleted successfully!');
    } catch (err: any) {
      console.error('Failed to delete account:', err);
      const errorMessage = err.response?.data?.message || err.response?.data?.error || 'Failed to delete account';
      setError(errorMessage);
      alert(`Error: ${errorMessage}`);
    } finally {
      setLoading(false);
    }
  };

  const handleTest = async (id: number) => {
    try {
      const response = await clickUpApi.testConnection(id);
      alert(`Connection successful! User: ${response.data.user.username}`);
    } catch (err) {
      alert('Connection failed!');
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
    },
    addButton: {
      padding: '0.5rem 1rem',
      backgroundColor: '#7b68ee',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    form: {
      backgroundColor: '#f8f9fa',
      padding: '1rem',
      borderRadius: '4px',
      marginBottom: '1rem',
    },
    inputGroup: {
      marginBottom: '1rem',
    },
    label: {
      display: 'block',
      marginBottom: '0.25rem',
      fontSize: '0.9rem',
      color: '#666',
    },
    input: {
      width: '100%',
      padding: '0.5rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
    },
    select: {
      width: '100%',
      padding: '0.5rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
    },
    buttonGroup: {
      display: 'flex',
      gap: '0.5rem',
    },
    submitButton: {
      padding: '0.5rem 1rem',
      backgroundColor: '#28a745',
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
      borderRadius: '4px',
      padding: '1rem',
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
    },
    accountInfo: {
      flex: 1,
    },
    accountName: {
      fontWeight: 'bold',
      marginBottom: '0.25rem',
    },
    accountEmail: {
      fontSize: '0.9rem',
      color: '#666',
    },
    accountActions: {
      display: 'flex',
      gap: '0.5rem',
    },
    actionButton: {
      padding: '0.25rem 0.75rem',
      fontSize: '0.85rem',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    testButton: {
      backgroundColor: '#17a2b8',
      color: 'white',
    },
    deleteButton: {
      backgroundColor: '#dc3545',
      color: 'white',
    },
    error: {
      color: '#dc3545',
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
        <h2 style={styles.title}>ClickUp Accounts</h2>
        {!showAddForm && (
          <button style={styles.addButton} onClick={() => setShowAddForm(true)}>
            Add Account
          </button>
        )}
      </div>

      {showAddForm && (
        <form style={styles.form} onSubmit={handleSubmit}>
          {error && <div style={styles.error}>{error}</div>}
          
          <div style={styles.inputGroup}>
            <label style={styles.label}>Account Name</label>
            <input
              type="text"
              style={styles.input}
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              placeholder="e.g., My Work Account"
              required
            />
          </div>

          <div style={styles.inputGroup}>
            <label style={styles.label}>Access Token</label>
            <input
              type="password"
              style={styles.input}
              value={formData.access_token}
              onChange={(e) => setFormData({ ...formData, access_token: e.target.value })}
              placeholder="pk_..."
              required
            />
            <small style={{ color: '#666', fontSize: '0.8rem' }}>
              Get your token from ClickUp Settings → Apps → Personal Token
            </small>
          </div>

          <div style={styles.inputGroup}>
            <label style={styles.label}>Account Type</label>
            <select
              style={styles.select}
              value={formData.account_type}
              onChange={(e) => setFormData({ ...formData, account_type: e.target.value })}
            >
              <option value="personal">Personal Access Token</option>
              <option value="oauth">OAuth (Coming Soon)</option>
            </select>
          </div>

          <div style={styles.buttonGroup}>
            <button type="submit" style={styles.submitButton} disabled={loading}>
              {loading ? 'Adding...' : 'Add Account'}
            </button>
            <button
              type="button"
              style={styles.cancelButton}
              onClick={() => {
                setShowAddForm(false);
                setError('');
                setFormData({ name: '', access_token: '', account_type: 'personal' });
              }}
            >
              Cancel
            </button>
          </div>
        </form>
      )}

      <div style={styles.accountList}>
        {accounts.length === 0 ? (
          <div style={styles.empty}>No accounts connected yet. Add your first ClickUp account to get started!</div>
        ) : (
          accounts.map((account) => (
            <div key={account.id} style={styles.accountCard}>
              <div style={styles.accountInfo}>
                <div style={styles.accountName}>{account.name}</div>
                <div style={styles.accountEmail}>
                  {account.clickup_username} ({account.clickup_email})
                </div>
              </div>
              <div style={styles.accountActions}>
                <button
                  style={{ ...styles.actionButton, ...styles.testButton }}
                  onClick={() => handleTest(account.id)}
                >
                  Test Connection
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

export default ClickUpAccounts;