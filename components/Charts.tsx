import React from 'react';
import {
  ResponsiveContainer,
  BarChart, Bar,
  LineChart, Line,
  AreaChart, Area,
  PieChart, Pie, Cell,
  ScatterChart, Scatter,
  XAxis, YAxis, CartesianGrid,
  Tooltip, Legend
} from 'recharts';
import { DashboardWidget, DataRow } from '../types';

const COLORS = ['#0ea5e9', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#ec4899'];

interface ChartWidgetProps {
  widget: DashboardWidget;
  data: DataRow[];
}

/* ---------------- TOOLTIP ---------------- */

const CustomTooltip = ({ active, payload, label }: any) => {
  if (active && payload && payload.length) {
    return (
      <div className="glass-panel p-3 border border-slate-600 text-xs">
        <p className="text-slate-300 font-bold mb-1">{label}</p>
        {payload.map((entry: any, index: number) => (
          <p key={index} style={{ color: entry.color }}>
            {entry.name}: {Number(entry.value).toLocaleString()}
          </p>
        ))}
      </div>
    );
  }
  return null;
};

/* ---------------- MAIN COMPONENT ---------------- */

export const ChartWidget: React.FC<ChartWidgetProps> = ({ widget, data }) => {
  const { type: defaultType, dataKeyX, dataKeyY } = widget;

  /* 🔹 LOCAL OVERRIDE FOR CHART TYPE */
  const [chartType, setChartType] = React.useState(defaultType);

  /* 🔹 SAFE DATA PROCESSING */
  const chartData = React.useMemo(() => {
    if (!data || data.length === 0) return [];
    if (data.length <= 100) return data;

    const step = Math.ceil(data.length / 100);
    return data.filter((_, i) => i % step === 0);
  }, [data]);

  const commonProps = {
    data: chartData,
    margin: { top: 10, right: 10, left: 0, bottom: 0 }
  };

  /* ---------------- RENDER CHART ---------------- */

  const renderChart = () => {
    if (!chartData.length && chartType !== 'KPI') {
      return (
        <div className="flex items-center justify-center h-full text-slate-500">
          No data available
        </div>
      );
    }

    switch (chartType) {
      case 'BAR':
        return (
          <BarChart {...commonProps}>
            <CartesianGrid strokeDasharray="3 3" stroke="#334155" vertical={false} />
            <XAxis dataKey={dataKeyX} stroke="#94a3b8" fontSize={10} tickLine={false} />
            <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} />
            <Tooltip content={<CustomTooltip />} />
            <Bar dataKey={dataKeyY!} fill="#0ea5e9" radius={[4, 4, 0, 0]} />
          </BarChart>
        );

      case 'LINE':
        return (
          <LineChart {...commonProps}>
            <CartesianGrid strokeDasharray="3 3" stroke="#334155" vertical={false} />
            <XAxis dataKey={dataKeyX} stroke="#94a3b8" fontSize={10} tickLine={false} />
            <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} />
            <Tooltip content={<CustomTooltip />} />
            <Line type="monotone" dataKey={dataKeyY!} stroke="#8b5cf6" strokeWidth={2} dot={false} />
          </LineChart>
        );

      case 'AREA':
        return (
          <AreaChart {...commonProps}>
            <CartesianGrid strokeDasharray="3 3" stroke="#334155" vertical={false} />
            <XAxis dataKey={dataKeyX} stroke="#94a3b8" fontSize={10} tickLine={false} />
            <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} />
            <Tooltip content={<CustomTooltip />} />
            <Area type="monotone" dataKey={dataKeyY!} stroke="#10b981" fill="#10b981" fillOpacity={0.25} />
          </AreaChart>
        );

      case 'PIE':
        return (
          <PieChart>
            <Tooltip content={<CustomTooltip />} />
            <Pie
              data={chartData.slice(0, 10)}
              dataKey={dataKeyY!}
              nameKey={dataKeyX!}
              cx="50%"
              cy="50%"
              innerRadius={40}
              outerRadius={80}
              paddingAngle={4}
            >
              {chartData.slice(0, 10).map((_, index) => (
                <Cell key={index} fill={COLORS[index % COLORS.length]} />
              ))}
            </Pie>
            <Legend
              iconSize={8}
              layout="vertical"
              verticalAlign="middle"
              align="right"
              wrapperStyle={{ fontSize: '10px', color: '#cbd5e1' }}
            />
          </PieChart>
        );

      case 'SCATTER':
        return (
          <ScatterChart {...commonProps}>
            <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
            <XAxis type="number" dataKey={dataKeyX} stroke="#94a3b8" fontSize={10} />
            <YAxis type="number" dataKey={dataKeyY} stroke="#94a3b8" fontSize={10} />
            <Tooltip content={<CustomTooltip />} />
            <Scatter data={chartData} fill="#f59e0b" />
          </ScatterChart>
        );

      case 'KPI': {
        const values = chartData
          .map(d => Number(d[dataKeyY!] || 0))
          .filter(n => !isNaN(n));

        const avg = values.length
          ? values.reduce((a, b) => a + b, 0) / values.length
          : 0;

        return (
          <div className="flex flex-col items-center justify-center h-full pb-4">
            <div className="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-city-accent to-white">
              {avg.toFixed(1)}
            </div>
            <div className="text-sm text-slate-400 mt-2 uppercase tracking-widest">
              Average {dataKeyY?.replace(/_/g, ' ')}
            </div>
          </div>
        );
      }

      default:
        return (
          <div className="flex items-center justify-center h-full text-slate-500">
            Unsupported Chart Type
          </div>
        );
    }
  };

  /* ---------------- UI ---------------- */

  return (
    <div className="w-full h-[250px] mt-2">
      {/* 🔽 DROPDOWN (NOT FOR KPI) */}
      {defaultType !== 'KPI' && (
        <div className="flex justify-end mb-1">
          <select
            value={chartType}
            onChange={(e) => setChartType(e.target.value)}
            className="bg-slate-900 text-slate-300 text-xs border border-slate-700 rounded px-2 py-1"
          >
            <option value="LINE">Line</option>
            <option value="BAR">Bar</option>
            <option value="AREA">Area</option>
            <option value="PIE">Pie</option>
            <option value="SCATTER">Scatter</option>
          </select>
        </div>
      )}

      {chartType === 'KPI'
        ? renderChart()
        : <ResponsiveContainer width="100%" height="100%">{renderChart()}</ResponsiveContainer>
      }
    </div>
  );
};
