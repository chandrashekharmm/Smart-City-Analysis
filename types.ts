export interface DataRow {
  [key: string]: string | number;
}

export interface ColumnStats {
  min: number;
  max: number;
  avg: number;
  uniqueValues: number;
  type: 'number' | 'string' | 'date';
}

export interface DatasetMetadata {
  fileName: string;
  rowCount: number;
  columns: string[];
  stats: Record<string, ColumnStats>;
  raw: DataRow[];
}

export enum WidgetType {
  BAR = 'BAR',
  LINE = 'LINE',
  AREA = 'AREA',
  PIE = 'PIE',
  SCATTER = 'SCATTER',
  KPI = 'KPI'
}

export interface DashboardWidget {
  id: string;
  title: string;
  type: WidgetType;
  dataKeyX?: string;
  dataKeyY?: string; // Primary metric
  dataKeyZ?: string; // Secondary metric or grouping
  description?: string;
  gridArea?: string; // 'col-span-1' | 'col-span-2'
}

export interface AIInsight {
  summary: string;
  anomalies: string[];
  recommendations: string[];
}

export interface DashboardConfig {
  title: string;
  widgets: DashboardWidget[];
  insights: AIInsight;
}

export enum ViewMode {
  UPLOAD = 'UPLOAD',
  DASHBOARD = 'DASHBOARD',
  TWIN = 'TWIN',
  AI_CHAT = 'AI_CHAT'
}