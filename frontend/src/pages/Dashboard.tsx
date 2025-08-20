import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { clickUpApi, mondayApi, syncApi } from '../services/api';
import ClickUpAccounts from '../components/ClickUpAccounts';
import MondayAccounts from '../components/MondayAccounts';
import SyncConfigurations from '../components/SyncConfigurations';
import ClickUpDataViewer from '../components/ClickUpDataViewer';
import GanttPage from '../components/GanttPage';

const Dashboard: React.FC = () => {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState<'accounts' | 'monday' | 'syncs' | 'data' | 'gantt'>('accounts');
  const [accounts, setAccounts] = useState([]);
  const [mondayAccounts, setMondayAccounts] = useState([]);
  const [configurations, setConfigurations] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [accountsRes, mondayRes, configsRes] = await Promise.all([
        clickUpApi.getAccounts(),
        mondayApi.getAccounts(),
        syncApi.getConfigurations(),
      ]);
      setAccounts(accountsRes.data);
      setMondayAccounts(mondayRes.data);
      setConfigurations(configsRes.data);
    } catch (error) {
      console.error('Failed to load data:', error);
    } finally {
      setLoading(false);
    }
  };

  const styles = {
    container: {
      minHeight: '100vh',
      backgroundColor: '#f5f5f5',
    },
    header: {
      backgroundColor: 'white',
      padding: '1rem 2rem',
      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
    },
    logo: {
      fontSize: '1.5rem',
      fontWeight: 'bold',
      color: '#7b68ee',
    },
    userSection: {
      display: 'flex',
      alignItems: 'center',
      gap: '1rem',
    },
    logoutButton: {
      padding: '0.5rem 1rem',
      backgroundColor: '#dc3545',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    content: {
      maxWidth: '1200px',
      margin: '2rem auto',
      padding: '0 1rem',
    },
    tabs: {
      display: 'flex',
      gap: '1rem',
      marginBottom: '2rem',
      borderBottom: '2px solid #ddd',
    },
    tab: {
      padding: '1rem 2rem',
      backgroundColor: 'transparent',
      border: 'none',
      fontSize: '1rem',
      cursor: 'pointer',
      color: '#666',
      borderBottom: '3px solid transparent',
      transition: 'all 0.3s',
    },
    tabActive: {
      color: '#7b68ee',
      borderBottomColor: '#7b68ee',
    },
    stats: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
      gap: '1rem',
      marginBottom: '2rem',
    },
    statCard: {
      backgroundColor: 'white',
      padding: '1.5rem',
      borderRadius: '8px',
      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
    },
    statValue: {
      fontSize: '2rem',
      fontWeight: 'bold',
      color: '#7b68ee',
      marginBottom: '0.5rem',
    },
    statLabel: {
      color: '#666',
      fontSize: '0.9rem',
    },
  };

  if (loading) {
    return <div style={{ padding: '2rem', textAlign: 'center' }}>Loading...</div>;
  }

  return (
    <div style={styles.container}>
      <header style={styles.header}>
        <div style={styles.logo}>ClickUp Sync</div>
        <div style={styles.userSection}>
          <span>Welcome, {user?.name}</span>
          <button style={styles.logoutButton} onClick={logout}>
            Logout
          </button>
        </div>
      </header>

      <div style={styles.content}>
        <div style={styles.stats}>
          <div style={styles.statCard}>
            <div style={styles.statValue}>{accounts.length + mondayAccounts.length}</div>
            <div style={styles.statLabel}>Connected Accounts</div>
          </div>
          <div style={styles.statCard}>
            <div style={styles.statValue}>{configurations.length}</div>
            <div style={styles.statLabel}>Sync Configurations</div>
          </div>
          <div style={styles.statCard}>
            <div style={styles.statValue}>
              {configurations.filter((c: any) => c.is_active).length}
            </div>
            <div style={styles.statLabel}>Active Syncs</div>
          </div>
        </div>

        <div style={styles.tabs}>
          <button
            style={{
              ...styles.tab,
              ...(activeTab === 'accounts' ? styles.tabActive : {}),
            }}
            onClick={() => setActiveTab('accounts')}
          >
            ClickUp Accounts
          </button>
          <button
            style={{
              ...styles.tab,
              ...(activeTab === 'monday' ? styles.tabActive : {}),
            }}
            onClick={() => setActiveTab('monday')}
          >
            Monday.com Accounts
          </button>
          <button
            style={{
              ...styles.tab,
              ...(activeTab === 'syncs' ? styles.tabActive : {}),
            }}
            onClick={() => setActiveTab('syncs')}
          >
            Sync Configurations
          </button>
          <button
            style={{
              ...styles.tab,
              ...(activeTab === 'data' ? styles.tabActive : {}),
            }}
            onClick={() => setActiveTab('data')}
          >
            ClickUp Data
          </button>
          <button
            style={{
              ...styles.tab,
              ...(activeTab === 'gantt' ? styles.tabActive : {}),
            }}
            onClick={() => setActiveTab('gantt')}
          >
            Gantt Chart
          </button>
        </div>

        {activeTab === 'accounts' ? (
          <ClickUpAccounts accounts={accounts} onUpdate={loadData} />
        ) : activeTab === 'monday' ? (
          <MondayAccounts accounts={mondayAccounts} onUpdate={loadData} />
        ) : activeTab === 'syncs' ? (
          <SyncConfigurations 
            configurations={configurations} 
            accounts={accounts}
            onUpdate={loadData} 
          />
        ) : activeTab === 'data' ? (
          <ClickUpDataViewer accounts={accounts} />
        ) : (
          <GanttPage clickupAccounts={accounts} mondayAccounts={mondayAccounts} />
        )}
      </div>
    </div>
  );
};

export default Dashboard;