import React, { useState } from 'react';
import { syncApi } from '../services/api';
import CreateSyncModal from './CreateSyncModal';

interface SyncConfiguration {
  id: number;
  name: string;
  description: string;
  source_account: any;
  target_account: any;
  sync_direction: string;
  schedule_type: string;
  is_active: boolean;
  last_sync_at: string;
  sync_logs: any[];
}

interface Props {
  configurations: SyncConfiguration[];
  accounts: any[];
  onUpdate: () => void;
}

const SyncConfigurations: React.FC<Props> = ({ configurations, accounts, onUpdate }) => {
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [testingConnection, setTestingConnection] = useState<number | null>(null);

  const handleSyncNow = async (id: number) => {
    if (!window.confirm('Start sync now?')) return;
    
    try {
      await syncApi.syncNow(id);
      alert('Sync started successfully!');
      onUpdate();
    } catch (err: any) {
      alert(`Sync failed: ${err.response?.data?.message || 'Unknown error'}`);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Are you sure you want to delete this configuration?')) return;
    
    try {
      await syncApi.deleteConfiguration(id);
      onUpdate();
    } catch (err) {
      console.error('Failed to delete configuration:', err);
    }
  };

  const handleToggleActive = async (config: SyncConfiguration) => {
    try {
      await syncApi.updateConfiguration(config.id, { is_active: !config.is_active });
      onUpdate();
    } catch (err) {
      console.error('Failed to update configuration:', err);
    }
  };

  const handleTestConnection = async (id: number) => {
    setTestingConnection(id);
    try {
      const response = await syncApi.testConnection(id);
      if (response.data.success) {
        alert('✅ Connection test successful! Both source and target are accessible.');
      } else {
        let errorMessage = 'Connection test failed:\n';
        if (!response.data.source_connected) {
          errorMessage += `\n❌ Source: ${response.data.source_error || 'Unknown error'}`;
        } else {
          errorMessage += '\n✅ Source: Connected';
        }
        if (!response.data.target_connected) {
          errorMessage += `\n❌ Target: ${response.data.target_error || 'Unknown error'}`;
        } else {
          errorMessage += '\n✅ Target: Connected';
        }
        alert(errorMessage);
      }
    } catch (err: any) {
      console.error('Test connection error:', err);
      alert(`Connection test failed: ${err.response?.data?.error || err.message || 'Unknown error'}`);
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
    },
    createButton: {
      padding: '0.5rem 1rem',
      backgroundColor: '#7b68ee',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    configList: {
      display: 'flex',
      flexDirection: 'column' as const,
      gap: '1rem',
    },
    configCard: {
      border: '1px solid #e0e0e0',
      borderRadius: '8px',
      padding: '1.5rem',
    },
    configHeader: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      marginBottom: '1rem',
    },
    configName: {
      fontSize: '1.1rem',
      fontWeight: 'bold',
      marginBottom: '0.25rem',
    },
    configDescription: {
      fontSize: '0.9rem',
      color: '#666',
      marginBottom: '0.5rem',
    },
    statusBadge: {
      padding: '0.25rem 0.75rem',
      borderRadius: '12px',
      fontSize: '0.85rem',
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
    configDetails: {
      display: 'grid',
      gridTemplateColumns: 'repeat(2, 1fr)',
      gap: '1rem',
      marginBottom: '1rem',
      fontSize: '0.9rem',
    },
    detailItem: {
      display: 'flex',
      flexDirection: 'column' as const,
    },
    detailLabel: {
      color: '#666',
      marginBottom: '0.25rem',
    },
    detailValue: {
      color: '#333',
      fontWeight: '500',
    },
    configActions: {
      display: 'flex',
      gap: '0.5rem',
      borderTop: '1px solid #e0e0e0',
      paddingTop: '1rem',
    },
    actionButton: {
      padding: '0.5rem 1rem',
      fontSize: '0.9rem',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    syncButton: {
      backgroundColor: '#28a745',
      color: 'white',
    },
    toggleButton: {
      backgroundColor: '#ffc107',
      color: '#333',
    },
    deleteButton: {
      backgroundColor: '#dc3545',
      color: 'white',
    },
    logsButton: {
      backgroundColor: '#17a2b8',
      color: 'white',
    },
    testButton: {
      backgroundColor: '#6c757d',
      color: 'white',
    },
    empty: {
      textAlign: 'center' as const,
      padding: '2rem',
      color: '#666',
    },
    syncInfo: {
      display: 'flex',
      gap: '2rem',
      marginTop: '0.5rem',
      fontSize: '0.85rem',
      color: '#666',
    },
  };

  if (accounts.length === 0) {
    return (
      <div style={styles.container}>
        <div style={styles.empty}>
          Please add at least one ClickUp account before creating sync configurations.
        </div>
      </div>
    );
  }

  return (
    <>
      <div style={styles.container}>
        <div style={styles.header}>
          <h2 style={styles.title}>Sync Configurations</h2>
          <button style={styles.createButton} onClick={() => setShowCreateModal(true)}>
            Create Sync Configuration
          </button>
        </div>

        <div style={styles.configList}>
          {configurations.length === 0 ? (
            <div style={styles.empty}>
              No sync configurations yet. Create your first configuration to start syncing!
            </div>
          ) : (
            configurations.map((config) => (
              <div key={config.id} style={styles.configCard}>
                <div style={styles.configHeader}>
                  <div>
                    <div style={styles.configName}>{config.name}</div>
                    {config.description && (
                      <div style={styles.configDescription}>{config.description}</div>
                    )}
                  </div>
                  <div
                    style={{
                      ...styles.statusBadge,
                      ...(config.is_active ? styles.activeBadge : styles.inactiveBadge),
                    }}
                  >
                    {config.is_active ? 'Active' : 'Inactive'}
                  </div>
                </div>

                <div style={styles.configDetails}>
                  <div style={styles.detailItem}>
                    <span style={styles.detailLabel}>Source</span>
                    <span style={styles.detailValue}>
                      {config.source_account?.name || 'Unknown'}
                    </span>
                  </div>
                  <div style={styles.detailItem}>
                    <span style={styles.detailLabel}>Target</span>
                    <span style={styles.detailValue}>
                      {config.target_account?.name || 'Unknown'}
                    </span>
                  </div>
                  <div style={styles.detailItem}>
                    <span style={styles.detailLabel}>Direction</span>
                    <span style={styles.detailValue}>
                      {config.sync_direction === 'one_way' ? 'One Way' : 'Two Way'}
                    </span>
                  </div>
                  <div style={styles.detailItem}>
                    <span style={styles.detailLabel}>Schedule</span>
                    <span style={styles.detailValue}>
                      {config.schedule_type === 'manual' ? 'Manual' : 
                       config.schedule_type === 'interval' ? 'Interval' : 'Cron'}
                    </span>
                  </div>
                </div>

                {config.last_sync_at && (
                  <div style={styles.syncInfo}>
                    <span>Last sync: {new Date(config.last_sync_at).toLocaleString()}</span>
                    {config.sync_logs?.[0] && (
                      <span>
                        Status: {config.sync_logs[0].status} | 
                        Tasks: {config.sync_logs[0].tasks_synced}
                      </span>
                    )}
                  </div>
                )}

                <div style={styles.configActions}>
                  <button
                    style={{ ...styles.actionButton, ...styles.syncButton }}
                    onClick={() => handleSyncNow(config.id)}
                    disabled={!config.is_active}
                  >
                    Sync Now
                  </button>
                  <button
                    style={{ ...styles.actionButton, ...styles.testButton }}
                    onClick={() => handleTestConnection(config.id)}
                    disabled={testingConnection === config.id}
                  >
                    {testingConnection === config.id ? 'Testing...' : 'Test Connection'}
                  </button>
                  <button
                    style={{ ...styles.actionButton, ...styles.toggleButton }}
                    onClick={() => handleToggleActive(config)}
                  >
                    {config.is_active ? 'Deactivate' : 'Activate'}
                  </button>
                  <button
                    style={{ ...styles.actionButton, ...styles.logsButton }}
                    onClick={() => alert('Logs feature coming soon!')}
                  >
                    View Logs
                  </button>
                  <button
                    style={{ ...styles.actionButton, ...styles.deleteButton }}
                    onClick={() => handleDelete(config.id)}
                  >
                    Delete
                  </button>
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      {showCreateModal && (
        <CreateSyncModal
          accounts={accounts}
          onClose={() => setShowCreateModal(false)}
          onCreated={() => {
            setShowCreateModal(false);
            onUpdate();
          }}
        />
      )}
    </>
  );
};

export default SyncConfigurations;