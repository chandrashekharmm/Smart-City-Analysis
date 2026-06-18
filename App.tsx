import React, { useState, useEffect, useRef } from "react";
import jsPDF from "jspdf";
import html2canvas from "html2canvas";

import { DashboardConfig, DatasetMetadata, ViewMode } from "./types";
import { generateDashboardConfig, askCityAssistant } from "./services/geminiService";
import { FileUploader } from "./components/FileUploader";
import { ChartWidget } from "./components/Charts";
import { DigitalTwin } from "./components/DigitalTwin";
import {
  Card,
  Button,
  LoadingSpinner,
  IconDashboard,
  IconActivity,
  IconMap,
  IconCpu
} from "./components/UI";

const App = () => {
  const [viewMode, setViewMode] = useState<ViewMode>(ViewMode.UPLOAD);
  const [dataset, setDataset] = useState<DatasetMetadata | null>(null);
  const [config, setConfig] = useState<DashboardConfig | null>(null);
  const [loading, setLoading] = useState(false);

  const [chatInput, setChatInput] = useState("");
  const [chatHistory, setChatHistory] = useState<{ role: "user" | "ai"; text: string }[]>([]);
  const chatEndRef = useRef<HTMLDivElement>(null);

  /* =====================================================
     EXPORT REPORT — 4 CHARTS PER PAGE (FINAL)
     ===================================================== */
  const handleExportReport = async () => {
    const pdf = new jsPDF("p", "pt", "a4");
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();

    const marginX = 40;
    const marginY = 40;

    /* =============================
       1️⃣ EXPORT SUMMARY / TEXT
       ============================= */
    let yOffset = marginY;

    const normalBlocks = document.querySelectorAll(".pdf-block:not(.pdf-chart)");

    for (const block of Array.from(normalBlocks)) {
      const canvas = await html2canvas(block as HTMLElement, {
        scale: 2,
        backgroundColor: "#020617",
        useCORS: true
      });

      const imgWidth = pageWidth - marginX * 2;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;

      if (yOffset + imgHeight > pageHeight - marginY) {
        pdf.addPage();
        yOffset = marginY;
      }

      pdf.addImage(
        canvas.toDataURL("image/png"),
        "PNG",
        marginX,
        yOffset,
        imgWidth,
        imgHeight
      );

      yOffset += imgHeight + 30;
    }

    /* =============================
       2️⃣ EXPORT CHARTS (4 PER PAGE)
       ============================= */
    const chartBlocks = Array.from(document.querySelectorAll(".pdf-chart"));

    for (let i = 0; i < chartBlocks.length; i += 4) {
      const group = chartBlocks.slice(i, i + 4);

      // Create temporary 2×2 grid
      const grid = document.createElement("div");
      grid.style.width = "794px";
      grid.style.display = "grid";
      grid.style.gridTemplateColumns = "1fr 1fr";
      grid.style.gap = "24px";
      grid.style.padding = "24px";
      grid.style.background = "#020617";

      group.forEach(card => {
        grid.appendChild(card.cloneNode(true));
      });

      document.body.appendChild(grid);

      const canvas = await html2canvas(grid, {
        scale: 2,
        backgroundColor: "#020617",
        useCORS: true
      });

      document.body.removeChild(grid);

      pdf.addPage();

      const imgWidth = pageWidth - marginX * 2;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;

      pdf.addImage(
        canvas.toDataURL("image/png"),
        "PNG",
        marginX,
        marginY,
        imgWidth,
        imgHeight
      );
    }

    /* =============================
       3️⃣ SAVE
       ============================= */
    pdf.save(
      `Smart_City_Command_Hub_Report_${new Date()
        .toISOString()
        .slice(0, 10)}.pdf`
    );
  };

  /* =============================
     DATA LOAD
     ============================= */
  const handleDataLoaded = async (data: DatasetMetadata) => {
    setDataset(data);
    setLoading(true);
    const generatedConfig = await generateDashboardConfig(data);
    setConfig(generatedConfig);
    setLoading(false);
    setViewMode(ViewMode.DASHBOARD);
  };

  /* =============================
     CHAT
     ============================= */
  const handleChatSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!chatInput.trim() || !dataset) return;

    setChatHistory(prev => [...prev, { role: "user", text: chatInput }]);
    setChatInput("");

    const context = `Columns: ${dataset.columns.join(", ")}, Rows: ${dataset.rowCount}`;
    const response = await askCityAssistant(chatInput, context);

    setChatHistory(prev => [...prev, { role: "ai", text: response }]);
  };

  useEffect(() => {
    chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [chatHistory]);

  /* =============================
     UPLOAD VIEW
     ============================= */
  if (viewMode === ViewMode.UPLOAD) {
    return (
      <div className="min-h-screen bg-city-dark flex items-center justify-center">
        {loading ? (
          <div className="flex flex-col items-center">
            <LoadingSpinner />
            <p className="mt-4 text-city-accent font-mono animate-pulse">
              Preparing your Smart City Dashboard...
            </p>
          </div>
        ) : (
          <FileUploader onDataLoaded={handleDataLoaded} />
        )}
      </div>
    );
  }

  /* =============================
     MAIN UI
     ============================= */
  return (
    <div className="min-h-screen bg-city-dark text-slate-200 flex overflow-hidden">

      <aside className="w-20 lg:w-64 bg-slate-900/50 border-r border-slate-800 flex flex-col">
        <div className="h-16 flex items-center justify-center border-b border-slate-800">
          <div className="w-8 h-8 bg-city-accent rounded flex items-center justify-center text-black font-bold">
            city
          </div>
        </div>
        <nav className="flex-1 p-4 space-y-2">
          <NavButton active={viewMode === ViewMode.DASHBOARD} onClick={() => setViewMode(ViewMode.DASHBOARD)} icon={<IconDashboard />} label="Dashboard" />
<NavButton
  active={viewMode === ViewMode.TWIN}
  onClick={() => window.location.href = "http://localhost/parking_pass/"}
  icon={<IconMap />}
  label="Get Parking Pass"
/>
        </nav>
      </aside>

      <main className="flex-1 flex flex-col h-screen">
        <header className="h-16 border-b border-slate-800 flex items-center justify-between px-8 bg-slate-900/30">
          <h2 className="text-xl font-bold">{config?.title}</h2>
          <Button variant="primary" onClick={handleExportReport}>
            Export Report
          </Button>
        </header>

        <div className="flex-1 overflow-y-auto p-6">
          {viewMode === ViewMode.DASHBOARD && config && dataset && (
            <div className="space-y-6 max-w-7xl mx-auto">

              <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card className="lg:col-span-2 border-l-4 border-city-accent pdf-block">
                  <h3 className="text-lg font-bold mb-2">Executive Summary</h3>
                  <p className="text-slate-300">{config.insights.summary}</p>
                </Card>

                <Card title="Detected Anomalies" className="pdf-block">
                  <ul className="space-y-2 text-sm text-city-danger">
                    {config.insights.anomalies.map((a, i) => (
                      <li key={i}>⚠ {a}</li>
                    ))}
                  </ul>
                </Card>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {config.widgets.map(widget => (
                  <Card
                    key={widget.id}
                    title={widget.title}
                    className="min-h-[320px] pdf-block pdf-chart"
                  >
                    <p className="text-xs text-slate-500 mb-2">{widget.description}</p>
                    <ChartWidget widget={widget} data={dataset.raw} />
                  </Card>
                ))}
              </div>

            </div>
          )}
        </div>
      </main>
    </div>
  );
};

const NavButton = ({ active, onClick, icon, label }: any) => (
  <button
    onClick={onClick}
    className={`w-full flex items-center gap-3 p-3 rounded ${
      active ? "bg-city-accent text-black font-bold" : "text-slate-400 hover:bg-white/5"
    }`}
  >
    {icon}
    <span className="hidden lg:block">{label}</span>
  </button>
);

export default App;
