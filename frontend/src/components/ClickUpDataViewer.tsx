import React, { useState, useEffect } from 'react';
import { clickUpApi } from '../services/api';

interface ClickUpAccount {
  id: number;
  name: string;
  clickup_username: string;
  clickup_email: string;
  is_active: boolean;
}

interface Workspace {
  id: string;
  name: string;
  color: string;
  members: any[];
}

interface Space {
  id: string;
  name: string;
  color: string;
  private: boolean;
  statuses: any[];
  features: any;
}

interface List {
  id: string;
  name: string;
  task_count: number;
  space: { id: string; name: string };
  folder?: { id: string; name: string };
}

interface Task {
  id: string;
  name: string;
  status: { status: string; color: string };
  priority?: { priority: string; color: string };
  assignees: any[];
  due_date?: string;
  tags: any[];
}

interface Props {
  accounts: ClickUpAccount[];
}

const ClickUpDataViewer: React.FC<Props> = ({ accounts }) => {
  const [selectedAccount, setSelectedAccount] = useState<number | null>(null);
  const [workspaces, setWorkspaces] = useState<Workspace[]>([]);
  const [selectedWorkspace, setSelectedWorkspace] = useState<string | null>(null);
  const [spaces, setSpaces] = useState<Space[]>([]);
  const [selectedSpace, setSelectedSpace] = useState<string | null>(null);
  const [lists, setLists] = useState<List[]>([]);
  const [selectedList, setSelectedList] = useState<string | null>(null);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Load workspaces when account is selected
  useEffect(() => {
    if (selectedAccount) {
      loadWorkspaces();
    } else {
      setWorkspaces([]);
      setSelectedWorkspace(null);
      setSpaces([]);
      setSelectedSpace(null);
      setLists([]);
      setSelectedList(null);
      setTasks([]);
    }
  }, [selectedAccount]);

  // Load spaces when workspace is selected
  useEffect(() => {
    if (selectedAccount && selectedWorkspace) {
      loadSpaces();
    } else {
      setSpaces([]);
      setSelectedSpace(null);
      setLists([]);
      setSelectedList(null);
      setTasks([]);
    }
  }, [selectedWorkspace]);

  // Load lists when space is selected
  useEffect(() => {
    if (selectedAccount && selectedSpace) {
      loadLists();
    } else {
      setLists([]);
      setSelectedList(null);
      setTasks([]);
    }
  }, [selectedSpace]);

  // Load tasks when list is selected
  useEffect(() => {
    if (selectedAccount && selectedList) {
      loadTasks();
    } else {
      setTasks([]);
    }
  }, [selectedList]);

  const loadWorkspaces = async () => {
    if (!selectedAccount) return;
    setLoading(true);
    setError('');
    try {
      console.log('Loading workspaces for account:', selectedAccount);
      const response = await clickUpApi.getWorkspaces(selectedAccount);
      console.log('Workspaces response:', response);
      
      if (response && response.data) {
        setWorkspaces(Array.isArray(response.data) ? response.data : []);
      } else {
        console.error('Invalid response structure:', response);
        setError('Invalid response from server');
      }
    } catch (err: any) {
      console.error('Workspace loading error:', err);
      console.error('Error response:', err.response);
      
      let errorMessage = 'Failed to load workspaces';
      if (err.response?.status === 401) {
        errorMessage += ': Authentication failed. Please check your ClickUp token.';
      } else if (err.response?.status === 403) {
        errorMessage += ': Access denied. Please check your ClickUp permissions.';
      } else if (err.response?.data?.error) {
        errorMessage += ': ' + err.response.data.error;
      } else if (err.response?.data?.message) {
        errorMessage += ': ' + err.response.data.message;
      } else if (err.message) {
        errorMessage += ': ' + err.message;
      }
      
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const loadSpaces = async () => {
    if (!selectedAccount || !selectedWorkspace) return;
    setLoading(true);
    setError('');
    try {
      const response = await clickUpApi.getSpaces(selectedAccount, selectedWorkspace);
      setSpaces(response.data || []);
    } catch (err: any) {
      console.error('Spaces loading error:', err);
      setError('Failed to load spaces: ' + (err.response?.data?.error || err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const loadLists = async () => {
    if (!selectedAccount || !selectedSpace) return;
    setLoading(true);
    setError('');
    try {
      const response = await clickUpApi.getLists(selectedAccount, { space_id: selectedSpace });
      setLists(response.data || []);
    } catch (err: any) {
      console.error('Lists loading error:', err);
      setError('Failed to load lists: ' + (err.response?.data?.error || err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const loadTasks = async () => {
    if (!selectedAccount || !selectedList) return;
    setLoading(true);
    setError('');
    try {
      const response = await clickUpApi.getTasks(selectedAccount, selectedList);
      setTasks(response.data.tasks || []);
    } catch (err: any) {
      console.error('Tasks loading error:', err);
      setError('Failed to load tasks: ' + (err.response?.data?.error || err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  const styles = {
    container: {
      backgroundColor: 'white',
      borderRadius: '8px',
      padding: '1.5rem',
      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
    },
    title: {
      fontSize: '1.25rem',
      fontWeight: 'bold',
      color: '#333',
      marginBottom: '1.5rem',
    },
    section: {
      marginBottom: '2rem',
    },
    sectionTitle: {
      fontSize: '1rem',
      fontWeight: 'bold',
      color: '#666',
      marginBottom: '0.75rem',
    },
    select: {
      width: '100%',
      padding: '0.75rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
      fontSize: '0.9rem',
      marginBottom: '1rem',
    },
    grid: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
      gap: '1rem',
    },
    card: {
      border: '1px solid #e0e0e0',
      borderRadius: '8px',
      padding: '1rem',
      backgroundColor: '#f8f9fa',
    },
    cardTitle: {
      fontWeight: 'bold',
      marginBottom: '0.5rem',
    },
    cardContent: {
      fontSize: '0.9rem',
      color: '#666',
    },
    badge: {
      display: 'inline-block',
      padding: '0.25rem 0.5rem',
      backgroundColor: '#7b68ee',
      color: 'white',
      borderRadius: '12px',
      fontSize: '0.8rem',
      marginRight: '0.5rem',
      marginBottom: '0.25rem',
    },
    statusBadge: {
      display: 'inline-block',
      padding: '0.25rem 0.5rem',
      borderRadius: '12px',
      fontSize: '0.8rem',
      marginRight: '0.5rem',
      color: 'white',
      fontWeight: 'bold',
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
    empty: {
      textAlign: 'center' as const,
      padding: '2rem',
      color: '#999',
      fontStyle: 'italic',
    },
  };

  if (accounts.length === 0) {
    return (
      <div style={styles.container}>
        <h2 style={styles.title}>ClickUp Data Viewer</h2>
        <div style={styles.empty}>No ClickUp accounts connected. Please add an account first.</div>
      </div>
    );
  }

  return (
    <div style={styles.container}>
      <h2 style={styles.title}>ClickUp Data Viewer</h2>
      
      {error && <div style={styles.error}>{error}</div>}

      <div style={styles.section}>
        <div style={styles.sectionTitle}>Select Account</div>
        <select
          style={styles.select}
          value={selectedAccount || ''}
          onChange={(e) => setSelectedAccount(e.target.value ? Number(e.target.value) : null)}
        >
          <option value="">Choose a ClickUp account...</option>
          {accounts.map((account) => (
            <option key={account.id} value={account.id}>
              {account.name} ({account.clickup_username})
            </option>
          ))}
        </select>
      </div>

      {selectedAccount && (
        <div style={styles.section}>
          <div style={styles.sectionTitle}>Workspaces ({workspaces.length})</div>
          {loading && <div style={styles.loading}>Loading workspaces...</div>}
          {!loading && workspaces.length === 0 && (
            <div style={styles.empty}>No workspaces found</div>
          )}
          {!loading && workspaces.length > 0 && (
            <>
              <select
                style={styles.select}
                value={selectedWorkspace || ''}
                onChange={(e) => setSelectedWorkspace(e.target.value || null)}
              >
                <option value="">Select a workspace...</option>
                {workspaces.map((workspace) => (
                  <option key={workspace.id} value={workspace.id}>
                    {workspace.name} ({workspace.members?.length || 0} members)
                  </option>
                ))}
              </select>
              <div style={styles.grid}>
                {workspaces.map((workspace) => (
                  <div key={workspace.id} style={styles.card}>
                    <div style={styles.cardTitle}>{workspace.name}</div>
                    <div style={styles.cardContent}>
                      <div>ID: {workspace.id}</div>
                      <div>Members: {workspace.members?.length || 0}</div>
                      <div style={{ marginTop: '0.5rem' }}>
                        <span 
                          style={{ 
                            ...styles.badge, 
                            backgroundColor: workspace.color || '#7b68ee' 
                          }}
                        >
                          {workspace.color || 'No Color'}
                        </span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </div>
      )}

      {selectedWorkspace && (
        <div style={styles.section}>
          <div style={styles.sectionTitle}>Spaces ({spaces.length})</div>
          {loading && <div style={styles.loading}>Loading spaces...</div>}
          {!loading && spaces.length === 0 && (
            <div style={styles.empty}>No spaces found</div>
          )}
          {!loading && spaces.length > 0 && (
            <>
              <select
                style={styles.select}
                value={selectedSpace || ''}
                onChange={(e) => setSelectedSpace(e.target.value || null)}
              >
                <option value="">Select a space...</option>
                {spaces.map((space) => (
                  <option key={space.id} value={space.id}>
                    {space.name} ({space.private ? 'Private' : 'Public'})
                  </option>
                ))}
              </select>
              <div style={styles.grid}>
                {spaces.map((space) => (
                  <div key={space.id} style={styles.card}>
                    <div style={styles.cardTitle}>{space.name}</div>
                    <div style={styles.cardContent}>
                      <div>ID: {space.id}</div>
                      <div>Type: {space.private ? 'Private' : 'Public'}</div>
                      <div>Statuses: {space.statuses?.length || 0}</div>
                      <div style={{ marginTop: '0.5rem' }}>
                        {space.statuses?.map((status: any) => (
                          <span
                            key={status.id}
                            style={{
                              ...styles.statusBadge,
                              backgroundColor: status.color,
                            }}
                          >
                            {status.status}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </div>
      )}

      {selectedSpace && (
        <div style={styles.section}>
          <div style={styles.sectionTitle}>Lists ({lists.length})</div>
          {loading && <div style={styles.loading}>Loading lists...</div>}
          {!loading && lists.length === 0 && (
            <div style={styles.empty}>No lists found</div>
          )}
          {!loading && lists.length > 0 && (
            <>
              <select
                style={styles.select}
                value={selectedList || ''}
                onChange={(e) => setSelectedList(e.target.value || null)}
              >
                <option value="">Select a list...</option>
                {lists.map((list) => (
                  <option key={list.id} value={list.id}>
                    {list.name} ({list.task_count} tasks)
                  </option>
                ))}
              </select>
              <div style={styles.grid}>
                {lists.map((list) => (
                  <div key={list.id} style={styles.card}>
                    <div style={styles.cardTitle}>{list.name}</div>
                    <div style={styles.cardContent}>
                      <div>ID: {list.id}</div>
                      <div>Tasks: {list.task_count}</div>
                      <div>Space: {list.space.name}</div>
                      {list.folder && <div>Folder: {list.folder.name}</div>}
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </div>
      )}

      {selectedList && (
        <div style={styles.section}>
          <div style={styles.sectionTitle}>Tasks ({tasks.length})</div>
          {loading && <div style={styles.loading}>Loading tasks...</div>}
          {!loading && tasks.length === 0 && (
            <div style={styles.empty}>No tasks found or tasks endpoint not implemented</div>
          )}
          {!loading && tasks.length > 0 && (
            <div style={styles.grid}>
              {tasks.map((task) => (
                <div key={task.id} style={styles.card}>
                  <div style={styles.cardTitle}>{task.name}</div>
                  <div style={styles.cardContent}>
                    <div>ID: {task.id}</div>
                    {task.status && (
                      <div>
                        Status: 
                        <span
                          style={{
                            ...styles.statusBadge,
                            backgroundColor: task.status.color,
                            marginLeft: '0.5rem',
                          }}
                        >
                          {task.status.status}
                        </span>
                      </div>
                    )}
                    {task.priority && (
                      <div>Priority: {task.priority.priority}</div>
                    )}
                    {task.assignees && task.assignees.length > 0 && (
                      <div>Assignees: {task.assignees.length}</div>
                    )}
                    {task.due_date && (
                      <div>Due: {new Date(parseInt(task.due_date)).toLocaleDateString()}</div>
                    )}
                    {task.tags && task.tags.length > 0 && (
                      <div style={{ marginTop: '0.5rem' }}>
                        {task.tags.map((tag: any, index: number) => (
                          <span key={index} style={styles.badge}>
                            {tag.name}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default ClickUpDataViewer;