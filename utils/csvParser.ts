import { DataRow, ColumnStats } from '../types';

export const parseCSV = (text: string): DataRow[] => {
  const lines = text.split('\n').filter(l => l.trim() !== '');
  if (lines.length === 0) return [];

  const headers = lines[0].split(',').map(h => h.trim().replace(/['"]+/g, ''));
  const result: DataRow[] = [];

  for (let i = 1; i < lines.length; i++) {
    const currentLine = lines[i].split(','); // Simple split, doesn't handle commas in quotes
    if (currentLine.length === headers.length) {
      const row: DataRow = {};
      headers.forEach((header, index) => {
        const val = currentLine[index]?.trim();
        const numVal = Number(val);
        row[header] = isNaN(numVal) || val === '' ? val : numVal;
      });
      result.push(row);
    }
  }
  return result;
};

export const calculateStats = (data: DataRow[]): Record<string, ColumnStats> => {
  if (data.length === 0) return {};
  const keys = Object.keys(data[0]);
  const stats: Record<string, ColumnStats> = {};

  keys.forEach(key => {
    const values = data.map(d => d[key]);
    const numericValues = values.filter(v => typeof v === 'number') as number[];
    const isNumeric = numericValues.length > values.length * 0.8; // Heuristic

    if (isNumeric && numericValues.length > 0) {
      stats[key] = {
        min: Math.min(...numericValues),
        max: Math.max(...numericValues),
        avg: numericValues.reduce((a, b) => a + b, 0) / numericValues.length,
        uniqueValues: new Set(values).size,
        type: 'number'
      };
    } else {
      stats[key] = {
        min: 0,
        max: 0,
        avg: 0,
        uniqueValues: new Set(values).size,
        type: 'string'
      };
    }
  });

  return stats;
};