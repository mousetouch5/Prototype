import React, { useState, useEffect, useMemo } from 'react';

interface GanttTask {
  id: string;
  name: string;
  start: string | null;
  end: string | null;
  progress: number;
  status: string;
  status_color: string;
  assignees: string[];
  priority?: string;
  priority_color?: string;
  dependencies?: string[];
  url?: string;
  platform: string;
}

interface Props {
  tasks: GanttTask[];
  onTaskClick?: (task: GanttTask) => void;
  height?: number;
}

const GanttChart: React.FC<Props> = ({ tasks, onTaskClick, height = 400 }) => {
  const [viewMode, setViewMode] = useState<'day' | 'week' | 'month'>('week');
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedTask, setSelectedTask] = useState<string | null>(null);

  // Calculate date range for the chart
  const dateRange = useMemo(() => {
    if (tasks.length === 0) {
      const start = new Date();
      const end = new Date();
      end.setMonth(end.getMonth() + 1);
      return { start, end };
    }

    const validTasks = tasks.filter(task => task.start && task.end);
    if (validTasks.length === 0) {
      const start = new Date();
      const end = new Date();
      end.setMonth(end.getMonth() + 1);
      return { start, end };
    }

    const dates = validTasks.flatMap(task => [
      new Date(task.start!),
      new Date(task.end!)
    ]);

    const start = new Date(Math.min(...dates.map(d => d.getTime())));
    const end = new Date(Math.max(...dates.map(d => d.getTime())));

    // Add some padding
    start.setDate(start.getDate() - 7);
    end.setDate(end.getDate() + 7);

    return { start, end };
  }, [tasks]);

  // Generate time columns based on view mode
  const timeColumns = useMemo(() => {
    const columns = [];
    const current = new Date(dateRange.start);
    const end = dateRange.end;

    while (current <= end) {
      columns.push(new Date(current));
      
      switch (viewMode) {
        case 'day':
          current.setDate(current.getDate() + 1);
          break;
        case 'week':
          current.setDate(current.getDate() + 7);
          break;
        case 'month':
          current.setMonth(current.getMonth() + 1);
          break;
      }
    }

    return columns;
  }, [dateRange, viewMode]);

  const formatColumnHeader = (date: Date): string => {
    switch (viewMode) {
      case 'day':
        return date.toLocaleDateString('en-US', { 
          month: 'short', 
          day: 'numeric' 
        });
      case 'week':
        return `Week ${getWeekNumber(date)}`;
      case 'month':
        return date.toLocaleDateString('en-US', { 
          month: 'short', 
          year: 'numeric' 
        });
      default:
        return date.toDateString();
    }
  };

  const getWeekNumber = (date: Date): number => {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + 3 - (d.getDay() + 6) % 7);
    const week1 = new Date(d.getFullYear(), 0, 4);
    return 1 + Math.round(((d.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
  };

  const getTaskBarStyle = (task: GanttTask): React.CSSProperties => {
    if (!task.start || !task.end) {
      return { display: 'none' };
    }

    const startDate = new Date(task.start);
    const endDate = new Date(task.end);
    const totalDuration = dateRange.end.getTime() - dateRange.start.getTime();
    const taskStart = startDate.getTime() - dateRange.start.getTime();
    const taskDuration = endDate.getTime() - startDate.getTime();

    const left = (taskStart / totalDuration) * 100;
    const width = (taskDuration / totalDuration) * 100;

    return {
      position: 'absolute',
      left: `${Math.max(0, left)}%`,
      width: `${Math.max(2, width)}%`,
      height: '20px',
      backgroundColor: task.status_color,
      borderRadius: '4px',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: '0 4px',
      fontSize: '10px',
      color: getContrastColor(task.status_color),
      cursor: 'pointer',
      border: selectedTask === task.id ? '2px solid #007bff' : 'none',
      zIndex: selectedTask === task.id ? 10 : 1,
    };
  };

  const getContrastColor = (hexColor: string): string => {
    const r = parseInt(hexColor.slice(1, 3), 16);
    const g = parseInt(hexColor.slice(3, 5), 16);
    const b = parseInt(hexColor.slice(5, 7), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.5 ? '#000000' : '#ffffff';
  };

  const handleTaskClick = (task: GanttTask) => {
    setSelectedTask(selectedTask === task.id ? null : task.id);
    onTaskClick?.(task);
  };

  const styles = {
    container: {
      backgroundColor: 'white',
      borderRadius: '8px',
      padding: '1rem',
      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
      height: `${height}px`,
      overflow: 'hidden',
      display: 'flex',
      flexDirection: 'column' as const,
    },
    header: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: '1rem',
      borderBottom: '1px solid #e0e0e0',
      paddingBottom: '1rem',
    },
    title: {
      fontSize: '1.25rem',
      fontWeight: 'bold',
      color: '#333',
    },
    controls: {
      display: 'flex',
      gap: '0.5rem',
    },
    viewButton: {
      padding: '0.25rem 0.75rem',
      border: '1px solid #ddd',
      borderRadius: '4px',
      backgroundColor: 'white',
      cursor: 'pointer',
      fontSize: '0.9rem',
    },
    viewButtonActive: {
      backgroundColor: '#7b68ee',
      color: 'white',
      borderColor: '#7b68ee',
    },
    chartContainer: {
      flex: 1,
      overflow: 'auto',
      border: '1px solid #e0e0e0',
      borderRadius: '4px',
    },
    chartHeader: {
      display: 'flex',
      backgroundColor: '#f8f9fa',
      borderBottom: '1px solid #e0e0e0',
      position: 'sticky' as const,
      top: 0,
      zIndex: 100,
    },
    taskColumn: {
      minWidth: '200px',
      width: '200px',
      padding: '0.5rem',
      borderRight: '1px solid #e0e0e0',
      fontWeight: 'bold',
      fontSize: '0.9rem',
    },
    timeColumn: {
      minWidth: '100px',
      padding: '0.5rem',
      borderRight: '1px solid #e0e0e0',
      textAlign: 'center' as const,
      fontSize: '0.8rem',
      fontWeight: 'bold',
    },
    taskRow: {
      display: 'flex',
      borderBottom: '1px solid #f0f0f0',
      minHeight: '40px',
      alignItems: 'center',
    },
    taskInfo: {
      minWidth: '200px',
      width: '200px',
      padding: '0.5rem',
      borderRight: '1px solid #e0e0e0',
      fontSize: '0.9rem',
    },
    taskName: {
      fontWeight: 'bold',
      marginBottom: '2px',
    },
    taskMeta: {
      fontSize: '0.75rem',
      color: '#666',
    },
    timeline: {
      flex: 1,
      position: 'relative' as const,
      height: '40px',
      padding: '10px 0',
    },
    progressBar: {
      position: 'absolute' as const,
      top: '10px',
      height: '20px',
      backgroundColor: 'rgba(0, 0, 0, 0.1)',
      borderRadius: '4px',
    },
    progressFill: {
      height: '100%',
      backgroundColor: 'rgba(255, 255, 255, 0.7)',
      borderRadius: '4px',
      transition: 'width 0.3s ease',
    },
    platformBadge: {
      padding: '2px 6px',
      borderRadius: '12px',
      fontSize: '0.7rem',
      fontWeight: 'bold',
      color: 'white',
      marginLeft: '4px',
    },
    empty: {
      textAlign: 'center' as const,
      padding: '2rem',
      color: '#666',
      fontStyle: 'italic',
    },
  };

  const getPlatformColor = (platform: string): string => {
    switch (platform.toLowerCase()) {
      case 'clickup':
        return '#7b68ee';
      case 'monday':
        return '#00D2FF';
      default:
        return '#666';
    }
  };

  if (tasks.length === 0) {
    return (
      <div style={styles.container}>
        <div style={styles.header}>
          <h2 style={styles.title}>Gantt Chart</h2>
        </div>
        <div style={styles.empty}>
          No tasks available for Gantt chart visualization.
          <br />
          Add tasks with start and end dates to see them here.
        </div>
      </div>
    );
  }

  return (
    <div style={styles.container}>
      <div style={styles.header}>
        <h2 style={styles.title}>Gantt Chart ({tasks.length} tasks)</h2>
        <div style={styles.controls}>
          {(['day', 'week', 'month'] as const).map(mode => (
            <button
              key={mode}
              style={{
                ...styles.viewButton,
                ...(viewMode === mode ? styles.viewButtonActive : {})
              }}
              onClick={() => setViewMode(mode)}
            >
              {mode.charAt(0).toUpperCase() + mode.slice(1)}
            </button>
          ))}
        </div>
      </div>

      <div style={styles.chartContainer}>
        <div style={styles.chartHeader}>
          <div style={styles.taskColumn}>Task</div>
          {timeColumns.map((date, index) => (
            <div key={index} style={styles.timeColumn}>
              {formatColumnHeader(date)}
            </div>
          ))}
        </div>

        {tasks.map(task => (
          <div key={task.id} style={styles.taskRow}>
            <div style={styles.taskInfo}>
              <div style={styles.taskName}>
                {task.name}
                <span 
                  style={{
                    ...styles.platformBadge,
                    backgroundColor: getPlatformColor(task.platform)
                  }}
                >
                  {task.platform}
                </span>
              </div>
              <div style={styles.taskMeta}>
                {task.status} • {task.assignees.join(', ') || 'Unassigned'}
                {task.priority && ` • ${task.priority}`}
              </div>
            </div>
            <div style={styles.timeline}>
              <div
                style={getTaskBarStyle(task)}
                onClick={() => handleTaskClick(task)}
                title={`${task.name}\nStatus: ${task.status}\nProgress: ${task.progress}%\nStart: ${task.start}\nEnd: ${task.end}`}
              >
                <span>{task.progress > 0 ? `${task.progress}%` : ''}</span>
                <div 
                  style={{
                    ...styles.progressFill,
                    width: `${task.progress}%`
                  }}
                />
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default GanttChart;