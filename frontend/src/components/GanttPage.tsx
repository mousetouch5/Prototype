import React, { useState, useEffect } from 'react';
import { ganttApi } from '../services/api';
import GanttChart from './GanttChart';


interface List {
  id: string;
  name: string;
  workspace: string;
  space: string;
  task_count: number;
}

interface Props {
  clickupAccounts: any[];
  mondayAccounts: any[];
}

const GanttPage: React.FC<Props> = ({ clickupAccounts, mondayAccounts }) => {
  const [selectedAccounts, setSelectedAccounts] = useState<{[key: string]: string[]}>({});
  const [availableLists, setAvailableLists] = useState<{[key: string]: List[]}>({});
  const [ganttData, setGanttData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const allAccounts = [
    ...clickupAccounts.map(acc => ({ ...acc, platform: 'clickup' })),
    ...mondayAccounts.map(acc => ({ ...acc, platform: 'monday' }))
  ];

  const loadAccountLists = async (platform: string, accountId: number) => {
    try {
      const response = await ganttApi.getAccountLists(platform, accountId);
      const key = `${platform}-${accountId}`;
      setAvailableLists(prev => ({
        ...prev,
        [key]: response.data
      }));
    } catch (err) {
      console.error('Failed to load lists:', err);
    }
  };

  const handleAccountToggle = (platform: string, accountId: number) => {
    const key = `${platform}-${accountId}`;
    
    if (selectedAccounts[key]) {
      // Remove account
      const newSelected = { ...selectedAccounts };
      delete newSelected[key];
      setSelectedAccounts(newSelected);
    } else {
      // Add account and load its lists
      setSelectedAccounts(prev => ({
        ...prev,
        [key]: []
      }));
      loadAccountLists(platform, accountId);
    }
  };

  const handleListToggle = (accountKey: string, listId: string) => {
    setSelectedAccounts(prev => ({
      ...prev,
      [accountKey]: prev[accountKey]?.includes(listId)
        ? prev[accountKey].filter(id => id !== listId)
        : [...(prev[accountKey] || []), listId]
    }));
  };

  const loadGanttData = async () => {
    setLoading(true);
    setError('');

    try {
      const accounts = Object.entries(selectedAccounts)
        .filter(([_, listIds]) => listIds.length > 0)
        .map(([accountKey, listIds]) => {
          const [platform, accountId] = accountKey.split('-');
          return {
            platform,
            account_id: parseInt(accountId),
            list_ids: listIds
          };
        });

      if (accounts.length === 0) {
        setGanttData([]);
        return;
      }

      const response = await ganttApi.getGanttData({ accounts });
      setGanttData(response.data.tasks);
    } catch (err: any) {
      setError('Failed to load Gantt data: ' + (err.response?.data?.error || err.message));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadGanttData();
  }, [selectedAccounts]);

  const styles = {
    container: {
      display: 'flex',
      flexDirection: 'column' as const,
      gap: '1rem',
      height: '100vh',
      padding: '1rem',
    },
    sidebar: {
      backgroundColor: 'white',
      borderRadius: '8px',
      padding: '1rem',
      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
      maxHeight: '400px',
      overflowY: 'auto' as const,
    },
    sidebarTitle: {
      fontSize: '1.1rem',
      fontWeight: 'bold',
      marginBottom: '1rem',
      color: '#333',
    },
    accountGroup: {
      marginBottom: '1rem',
    },
    accountHeader: {
      display: 'flex',
      alignItems: 'center',
      gap: '0.5rem',
      marginBottom: '0.5rem',
      cursor: 'pointer',
      padding: '0.5rem',
      borderRadius: '4px',
      backgroundColor: '#f8f9fa',
    },
    accountHeaderActive: {
      backgroundColor: '#e3f2fd',
    },
    platformBadge: {
      padding: '2px 6px',
      borderRadius: '12px',
      fontSize: '0.7rem',
      fontWeight: 'bold',
      color: 'white',
    },
    clickupBadge: {
      backgroundColor: '#7b68ee',
    },
    mondayBadge: {
      backgroundColor: '#00D2FF',
    },
    listGroup: {
      marginLeft: '1rem',
      borderLeft: '2px solid #e0e0e0',
      paddingLeft: '0.5rem',
    },
    listItem: {
      display: 'flex',
      alignItems: 'center',
      gap: '0.5rem',
      padding: '0.25rem 0',
      fontSize: '0.9rem',
    },
    checkbox: {
      margin: 0,
    },
    mainContent: {
      flex: 1,
      display: 'flex',
      flexDirection: 'column' as const,
    },
    error: {
      color: '#dc3545',
      backgroundColor: '#f8d7da',
      border: '1px solid #f5c6cb',
      borderRadius: '4px',
      padding: '0.75rem',
      marginBottom: '1rem',
    },
    loading: {
      textAlign: 'center' as const,
      padding: '2rem',
      color: '#666',
    },
    stats: {
      backgroundColor: 'white',
      borderRadius: '8px',
      padding: '1rem',
      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
      marginBottom: '1rem',
      display: 'flex',
      gap: '2rem',
      fontSize: '0.9rem',
    },
    statItem: {
      display: 'flex',
      flexDirection: 'column' as const,
      alignItems: 'center',
    },
    statValue: {
      fontSize: '1.5rem',
      fontWeight: 'bold',
      color: '#333',
    },
    statLabel: {
      color: '#666',
    },
  };

  const getPlatformColor = (platform: string) => {
    return platform === 'clickup' ? '#7b68ee' : '#00D2FF';
  };

  const selectedTaskCount = ganttData.length;
  const platformCount = Array.from(new Set(ganttData.map((task: any) => task.platform))).length;
  const tasksWithDates = ganttData.filter((task: any) => task.start && task.end).length;

  return (
    <div style={styles.container}>
      <div style={styles.sidebar}>
        <h3 style={styles.sidebarTitle}>Select Accounts & Lists</h3>
        
        {allAccounts.map(account => {
          const accountKey = `${account.platform}-${account.id}`;
          const isSelected = accountKey in selectedAccounts;
          const lists = availableLists[accountKey] || [];
          
          return (
            <div key={accountKey} style={styles.accountGroup}>
              <div
                style={{
                  ...styles.accountHeader,
                  ...(isSelected ? styles.accountHeaderActive : {})
                }}
                onClick={() => handleAccountToggle(account.platform, account.id)}
              >
                <input
                  type="checkbox"
                  checked={isSelected}
                  onChange={() => {}}
                  style={styles.checkbox}
                />
                <span
                  style={{
                    ...styles.platformBadge,
                    ...(account.platform === 'clickup' ? styles.clickupBadge : styles.mondayBadge)
                  }}
                >
                  {account.platform}
                </span>
                <span>{account.name}</span>
              </div>
              
              {isSelected && (
                <div style={styles.listGroup}>
                  {lists.map(list => (
                    <div key={list.id} style={styles.listItem}>
                      <input
                        type="checkbox"
                        checked={selectedAccounts[accountKey]?.includes(list.id) || false}
                        onChange={() => handleListToggle(accountKey, list.id)}
                        style={styles.checkbox}
                      />
                      <span>{list.name}</span>
                      <span style={{ color: '#666', fontSize: '0.8rem' }}>
                        ({list.task_count} tasks)
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          );
        })}
      </div>

      <div style={styles.mainContent}>
        {error && <div style={styles.error}>{error}</div>}
        
        {selectedTaskCount > 0 && (
          <div style={styles.stats}>
            <div style={styles.statItem}>
              <div style={styles.statValue}>{selectedTaskCount}</div>
              <div style={styles.statLabel}>Total Tasks</div>
            </div>
            <div style={styles.statItem}>
              <div style={styles.statValue}>{platformCount}</div>
              <div style={styles.statLabel}>Platforms</div>
            </div>
            <div style={styles.statItem}>
              <div style={styles.statValue}>{tasksWithDates}</div>
              <div style={styles.statLabel}>With Dates</div>
            </div>
          </div>
        )}

        {loading ? (
          <div style={styles.loading}>Loading Gantt data...</div>
        ) : (
          <GanttChart 
            tasks={ganttData}
            height={600}
            onTaskClick={(task) => {
              if (task.url) {
                window.open(task.url, '_blank');
              }
            }}
          />
        )}
      </div>
    </div>
  );
};

export default GanttPage;