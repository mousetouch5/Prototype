import React, { useState, useEffect } from 'react';
import { syncApi, clickUpApi } from '../services/api';

interface Props {
  accounts: any[];
  onClose: () => void;
  onCreated: () => void;
}

const CreateSyncModal: React.FC<Props> = ({ accounts, onClose, onCreated }) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    source_account_id: '',
    source_workspace_id: '',
    source_space_id: '',
    source_list_id: '',
    target_account_id: '',
    target_workspace_id: '',
    target_space_id: '',
    target_list_id: '',
    sync_direction: 'one_way',
    conflict_resolution: 'source_wins',
    sync_attachments: false,
    sync_comments: false,
    sync_custom_fields: true,
    schedule_type: 'manual',
    schedule_interval: 60,
  });

  const [sourceWorkspaces, setSourceWorkspaces] = useState([]);
  const [sourceSpaces, setSourceSpaces] = useState([]);
  const [sourceLists, setSourceLists] = useState([]);
  const [targetWorkspaces, setTargetWorkspaces] = useState([]);
  const [targetSpaces, setTargetSpaces] = useState([]);
  const [targetLists, setTargetLists] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (formData.source_account_id) {
      loadSourceWorkspaces();
    }
  }, [formData.source_account_id]);

  useEffect(() => {
    if (formData.source_workspace_id) {
      loadSourceSpaces();
    }
  }, [formData.source_workspace_id]);

  useEffect(() => {
    if (formData.source_space_id) {
      loadSourceLists();
    }
  }, [formData.source_space_id]);

  useEffect(() => {
    if (formData.target_account_id) {
      loadTargetWorkspaces();
    }
  }, [formData.target_account_id]);

  useEffect(() => {
    if (formData.target_workspace_id) {
      loadTargetSpaces();
    }
  }, [formData.target_workspace_id]);

  useEffect(() => {
    if (formData.target_space_id) {
      loadTargetLists();
    }
  }, [formData.target_space_id]);

  const loadSourceWorkspaces = async () => {
    try {
      const response = await clickUpApi.getWorkspaces(parseInt(formData.source_account_id));
      setSourceWorkspaces(response.data);
    } catch (err) {
      console.error('Failed to load source workspaces:', err);
    }
  };

  const loadSourceSpaces = async () => {
    try {
      const response = await clickUpApi.getSpaces(
        parseInt(formData.source_account_id),
        formData.source_workspace_id
      );
      setSourceSpaces(response.data);
    } catch (err) {
      console.error('Failed to load source spaces:', err);
    }
  };

  const loadSourceLists = async () => {
    try {
      const response = await clickUpApi.getLists(
        parseInt(formData.source_account_id),
        { space_id: formData.source_space_id }
      );
      setSourceLists(response.data);
    } catch (err) {
      console.error('Failed to load source lists:', err);
    }
  };

  const loadTargetWorkspaces = async () => {
    try {
      const response = await clickUpApi.getWorkspaces(parseInt(formData.target_account_id));
      setTargetWorkspaces(response.data);
    } catch (err) {
      console.error('Failed to load target workspaces:', err);
    }
  };

  const loadTargetSpaces = async () => {
    try {
      const response = await clickUpApi.getSpaces(
        parseInt(formData.target_account_id),
        formData.target_workspace_id
      );
      setTargetSpaces(response.data);
    } catch (err) {
      console.error('Failed to load target spaces:', err);
    }
  };

  const loadTargetLists = async () => {
    try {
      const response = await clickUpApi.getLists(
        parseInt(formData.target_account_id),
        { space_id: formData.target_space_id }
      );
      setTargetLists(response.data);
    } catch (err) {
      console.error('Failed to load target lists:', err);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await syncApi.createConfiguration(formData);
      onCreated();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to create configuration');
    } finally {
      setLoading(false);
    }
  };

  const styles = {
    overlay: {
      position: 'fixed' as const,
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundColor: 'rgba(0,0,0,0.5)',
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      zIndex: 1000,
    },
    modal: {
      backgroundColor: 'white',
      borderRadius: '8px',
      padding: '2rem',
      maxWidth: '800px',
      width: '90%',
      maxHeight: '90vh',
      overflow: 'auto',
    },
    header: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: '1.5rem',
    },
    title: {
      fontSize: '1.5rem',
      fontWeight: 'bold',
    },
    closeButton: {
      background: 'none',
      border: 'none',
      fontSize: '1.5rem',
      cursor: 'pointer',
    },
    form: {
      display: 'flex',
      flexDirection: 'column' as const,
      gap: '1rem',
    },
    row: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: '1rem',
    },
    inputGroup: {
      display: 'flex',
      flexDirection: 'column' as const,
    },
    label: {
      marginBottom: '0.25rem',
      fontSize: '0.9rem',
      fontWeight: '500',
    },
    input: {
      padding: '0.5rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
    },
    select: {
      padding: '0.5rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
    },
    checkbox: {
      marginRight: '0.5rem',
    },
    section: {
      borderTop: '1px solid #e0e0e0',
      paddingTop: '1rem',
      marginTop: '1rem',
    },
    sectionTitle: {
      fontSize: '1.1rem',
      fontWeight: 'bold',
      marginBottom: '0.5rem',
    },
    buttons: {
      display: 'flex',
      justifyContent: 'flex-end',
      gap: '0.5rem',
      marginTop: '1.5rem',
    },
    submitButton: {
      padding: '0.75rem 1.5rem',
      backgroundColor: '#7b68ee',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    cancelButton: {
      padding: '0.75rem 1.5rem',
      backgroundColor: '#6c757d',
      color: 'white',
      border: 'none',
      borderRadius: '4px',
      cursor: 'pointer',
    },
    error: {
      color: '#dc3545',
      marginBottom: '1rem',
    },
  };

  return (
    <div style={styles.overlay}>
      <div style={styles.modal}>
        <div style={styles.header}>
          <h2 style={styles.title}>Create Sync Configuration</h2>
          <button style={styles.closeButton} onClick={onClose}>×</button>
        </div>

        {error && <div style={styles.error}>{error}</div>}

        <form style={styles.form} onSubmit={handleSubmit}>
          <div style={styles.inputGroup}>
            <label style={styles.label}>Configuration Name</label>
            <input
              type="text"
              style={styles.input}
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
            />
          </div>

          <div style={styles.inputGroup}>
            <label style={styles.label}>Description (Optional)</label>
            <input
              type="text"
              style={styles.input}
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            />
          </div>

          <div style={styles.section}>
            <h3 style={styles.sectionTitle}>Source Configuration</h3>
            <div style={styles.row}>
              <div style={styles.inputGroup}>
                <label style={styles.label}>Source Account</label>
                <select
                  style={styles.select}
                  value={formData.source_account_id}
                  onChange={(e) => setFormData({ ...formData, source_account_id: e.target.value })}
                  required
                >
                  <option value="">Select Account</option>
                  {accounts.map((account) => (
                    <option key={account.id} value={account.id}>
                      {account.name}
                    </option>
                  ))}
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Source Workspace</label>
                <select
                  style={styles.select}
                  value={formData.source_workspace_id}
                  onChange={(e) => setFormData({ ...formData, source_workspace_id: e.target.value })}
                  required
                  disabled={!sourceWorkspaces.length}
                >
                  <option value="">Select Workspace</option>
                  {sourceWorkspaces.map((workspace: any) => (
                    <option key={workspace.id} value={workspace.id}>
                      {workspace.name}
                    </option>
                  ))}
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Source Space</label>
                <select
                  style={styles.select}
                  value={formData.source_space_id}
                  onChange={(e) => setFormData({ ...formData, source_space_id: e.target.value })}
                  required
                  disabled={!sourceSpaces.length}
                >
                  <option value="">Select Space</option>
                  {sourceSpaces.map((space: any) => (
                    <option key={space.id} value={space.id}>
                      {space.name}
                    </option>
                  ))}
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Source List</label>
                <select
                  style={styles.select}
                  value={formData.source_list_id}
                  onChange={(e) => setFormData({ ...formData, source_list_id: e.target.value })}
                  required
                  disabled={!sourceLists.length}
                >
                  <option value="">Select List</option>
                  {sourceLists.map((list: any) => (
                    <option key={list.id} value={list.id}>
                      {list.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          <div style={styles.section}>
            <h3 style={styles.sectionTitle}>Target Configuration</h3>
            <div style={styles.row}>
              <div style={styles.inputGroup}>
                <label style={styles.label}>Target Account</label>
                <select
                  style={styles.select}
                  value={formData.target_account_id}
                  onChange={(e) => setFormData({ ...formData, target_account_id: e.target.value })}
                  required
                >
                  <option value="">Select Account</option>
                  {accounts.map((account) => (
                    <option key={account.id} value={account.id}>
                      {account.name}
                    </option>
                  ))}
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Target Workspace</label>
                <select
                  style={styles.select}
                  value={formData.target_workspace_id}
                  onChange={(e) => setFormData({ ...formData, target_workspace_id: e.target.value })}
                  required
                  disabled={!targetWorkspaces.length}
                >
                  <option value="">Select Workspace</option>
                  {targetWorkspaces.map((workspace: any) => (
                    <option key={workspace.id} value={workspace.id}>
                      {workspace.name}
                    </option>
                  ))}
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Target Space</label>
                <select
                  style={styles.select}
                  value={formData.target_space_id}
                  onChange={(e) => setFormData({ ...formData, target_space_id: e.target.value })}
                  required
                  disabled={!targetSpaces.length}
                >
                  <option value="">Select Space</option>
                  {targetSpaces.map((space: any) => (
                    <option key={space.id} value={space.id}>
                      {space.name}
                    </option>
                  ))}
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Target List</label>
                <select
                  style={styles.select}
                  value={formData.target_list_id}
                  onChange={(e) => setFormData({ ...formData, target_list_id: e.target.value })}
                  required
                  disabled={!targetLists.length}
                >
                  <option value="">Select List</option>
                  {targetLists.map((list: any) => (
                    <option key={list.id} value={list.id}>
                      {list.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          <div style={styles.section}>
            <h3 style={styles.sectionTitle}>Sync Settings</h3>
            <div style={styles.row}>
              <div style={styles.inputGroup}>
                <label style={styles.label}>Sync Direction</label>
                <select
                  style={styles.select}
                  value={formData.sync_direction}
                  onChange={(e) => setFormData({ ...formData, sync_direction: e.target.value })}
                >
                  <option value="one_way">One Way (Source → Target)</option>
                  <option value="two_way">Two Way (Bidirectional)</option>
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Conflict Resolution</label>
                <select
                  style={styles.select}
                  value={formData.conflict_resolution}
                  onChange={(e) => setFormData({ ...formData, conflict_resolution: e.target.value })}
                >
                  <option value="source_wins">Source Wins</option>
                  <option value="target_wins">Target Wins</option>
                </select>
              </div>

              <div style={styles.inputGroup}>
                <label style={styles.label}>Schedule Type</label>
                <select
                  style={styles.select}
                  value={formData.schedule_type}
                  onChange={(e) => setFormData({ ...formData, schedule_type: e.target.value })}
                >
                  <option value="manual">Manual</option>
                  <option value="interval">Interval</option>
                </select>
              </div>

              {formData.schedule_type === 'interval' && (
                <div style={styles.inputGroup}>
                  <label style={styles.label}>Interval (minutes)</label>
                  <input
                    type="number"
                    style={styles.input}
                    value={formData.schedule_interval}
                    onChange={(e) => setFormData({ ...formData, schedule_interval: parseInt(e.target.value) })}
                    min="5"
                  />
                </div>
              )}
            </div>

            <div style={{ marginTop: '1rem' }}>
              <label>
                <input
                  type="checkbox"
                  style={styles.checkbox}
                  checked={formData.sync_custom_fields}
                  onChange={(e) => setFormData({ ...formData, sync_custom_fields: e.target.checked })}
                />
                Sync Custom Fields
              </label>
              <br />
              <label>
                <input
                  type="checkbox"
                  style={styles.checkbox}
                  checked={formData.sync_comments}
                  onChange={(e) => setFormData({ ...formData, sync_comments: e.target.checked })}
                />
                Sync Comments
              </label>
              <br />
              <label>
                <input
                  type="checkbox"
                  style={styles.checkbox}
                  checked={formData.sync_attachments}
                  onChange={(e) => setFormData({ ...formData, sync_attachments: e.target.checked })}
                />
                Sync Attachments
              </label>
            </div>
          </div>

          <div style={styles.buttons}>
            <button type="button" style={styles.cancelButton} onClick={onClose}>
              Cancel
            </button>
            <button type="submit" style={styles.submitButton} disabled={loading}>
              {loading ? 'Creating...' : 'Create Configuration'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CreateSyncModal;