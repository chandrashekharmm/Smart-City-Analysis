import React, { useCallback } from 'react';
import { Button, IconUpload, IconDatabase, Card } from './UI';
import { parseCSV, calculateStats } from '../utils/csvParser';
import { DatasetMetadata } from '../types';

interface FileUploaderProps {
  onDataLoaded: (data: DatasetMetadata) => void;
}

const SAMPLE_CSV = `Timestamp,District,Energy_Usage_kWh,Traffic_Density,Air_Quality_Index,Temperature_C,Active_Sensors,Incident_Reports
2023-11-01 08:00,Downtown,450.2,85,42,18,120,0
2023-11-01 09:00,Downtown,480.5,92,45,19,120,1
2023-11-01 10:00,Downtown,510.1,88,48,20,119,0
2023-11-01 11:00,Downtown,530.4,80,50,22,120,0
2023-11-01 12:00,Downtown,580.2,75,55,23,120,2
2023-11-01 08:00,Industrial,1200.5,60,85,22,85,0
2023-11-01 09:00,Industrial,1250.2,65,88,23,85,0
2023-11-01 10:00,Industrial,1300.8,70,90,24,84,1
2023-11-01 11:00,Industrial,1350.1,72,92,25,85,0
2023-11-01 12:00,Industrial,1320.4,68,89,26,85,0
2023-11-01 08:00,Residential,150.2,20,15,17,200,0
2023-11-01 09:00,Residential,180.5,25,18,18,200,0
2023-11-01 10:00,Residential,160.1,22,16,19,200,0
2023-11-01 11:00,Residential,155.4,18,15,20,200,0
2023-11-01 12:00,Residential,140.2,15,14,21,200,0
`;

export const FileUploader: React.FC<FileUploaderProps> = ({ onDataLoaded }) => {
  const processData = useCallback((text: string, fileName: string) => {
    const raw = parseCSV(text);
    const stats = calculateStats(raw);
    
    const metadata: DatasetMetadata = {
      fileName,
      rowCount: raw.length,
      columns: raw.length > 0 ? Object.keys(raw[0]) : [],
      stats,
      raw
    };
    
    onDataLoaded(metadata);
  }, [onDataLoaded]);

  const handleFileChange = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      const text = e.target?.result as string;
      processData(text, file.name);
    };
    reader.readAsText(file);
  }, [processData]);

  const handleLoadSample = useCallback(() => {
    processData(SAMPLE_CSV, 'Smart_City_Sample_Data.csv');
  }, [processData]);

  return (
    <div className="flex flex-col items-center justify-center h-full w-full p-10">
      <div className="text-center mb-8">
        <h1 className="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-city-accent to-purple-500 mb-4">
          SMART CITY ANALYSIS<br />
        </h1>
        <p className="text-slate-400 text-lg max-w-md mx-auto">
        </p>
      </div>

      <Card className="w-full max-w-2xl border-dashed border-2 border-slate-700 bg-slate-900/50 hover:border-city-accent transition-colors duration-300">
        <div className="flex flex-col items-center justify-center p-12 space-y-6">
          <div className="p-4 bg-city-accent/10 rounded-full text-city-accent">
            <IconUpload />
          </div>
          <div className="text-center">
            <p className="text-xl font-semibold text-white">Upload CSV Data File</p>
            <p className="text-sm text-slate-500 mt-2">Supports sensor data, demographics, traffic logs, etc.</p>
          </div>
          <div className="relative">
            <input 
              type="file" 
              accept=".csv" 
              onChange={handleFileChange}
              className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
            />
            <Button>Select File</Button>
          </div>
          
          <div className="flex items-center gap-4 w-full pt-4 border-t border-slate-700/50 mt-4">
             <div className="h-px bg-slate-700 flex-1"></div>
             <span className="text-xs text-slate-500 uppercase">Or start instantly</span>
             <div className="h-px bg-slate-700 flex-1"></div>
          </div>

          <Button variant="secondary" onClick={handleLoadSample} className="flex items-center gap-2">
            <IconDatabase /> Load Sample Dataset
          </Button>
        </div>
      </Card>

      <div className="mt-12 grid grid-cols-3 gap-6 w-full max-w-4xl">
        {[
          
        ].map((item, i) => (
          <div key={i} className="text-center p-4 border border-slate-800 rounded-lg bg-slate-900/30">
            <h4 className="text-city-accent font-bold mb-1">{item.label}</h4>
            <p className="text-xs text-slate-500">{item.desc}</p>
          </div>
        ))}
      </div>
    </div>
  );
};