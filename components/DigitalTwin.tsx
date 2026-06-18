import React, { useEffect, useRef } from 'react';
import * as d3 from 'd3';
import { DataRow } from '../types';

interface DigitalTwinProps {
  data: DataRow[];
}

export const DigitalTwin: React.FC<DigitalTwinProps> = ({ data }) => {
  const svgRef = useRef<SVGSVGElement>(null);

  useEffect(() => {
    if (!data || data.length === 0 || !svgRef.current) return;

    const width = svgRef.current.clientWidth;
    const height = svgRef.current.clientHeight;
    
    const svg = d3.select(svgRef.current);
    svg.selectAll("*").remove(); // Clear previous

    // Simulation setup: Create nodes based on data rows (limit to 50 for performance in simulation)
    const nodes = data.slice(0, 50).map((d, i) => ({
      id: i,
      radius: Math.random() * 5 + 3,
      group: Math.floor(Math.random() * 4),
      val: Object.values(d).find(v => typeof v === 'number') as number || 10
    }));

    // Create links (simulate network connections)
    const links = [];
    for (let i = 0; i < nodes.length; i++) {
        // Connect to random nearby nodes
        if (i < nodes.length - 1) {
            links.push({ source: i, target: i + 1 });
        }
        if (Math.random() > 0.7) {
             links.push({ source: i, target: Math.floor(Math.random() * nodes.length) });
        }
    }

    const simulation = d3.forceSimulation(nodes as any)
      .force("link", d3.forceLink(links).id((d: any) => d.id).distance(50))
      .force("charge", d3.forceManyBody().strength(-50))
      .force("center", d3.forceCenter(width / 2, height / 2))
      .force("collide", d3.forceCollide().radius(10));

    // Draw grid background (Sci-fi feel)
    const gridSize = 40;
    for(let x=0; x<width; x+=gridSize) {
        svg.append("line").attr("x1", x).attr("y1", 0).attr("x2", x).attr("y2", height).attr("stroke", "#1e293b").attr("stroke-width", 1);
    }
    for(let y=0; y<height; y+=gridSize) {
        svg.append("line").attr("x1", 0).attr("y1", y).attr("x2", width).attr("y2", y).attr("stroke", "#1e293b").attr("stroke-width", 1);
    }

    // Draw links
    const link = svg.append("g")
      .attr("stroke", "#0ea5e9")
      .attr("stroke-opacity", 0.3)
      .selectAll("line")
      .data(links)
      .join("line")
      .attr("stroke-width", 1);

    // Draw nodes
    const node = svg.append("g")
      .attr("stroke", "#fff")
      .attr("stroke-width", 1.5)
      .selectAll("circle")
      .data(nodes)
      .join("circle")
      .attr("r", d => d.radius)
      .attr("fill", d => d.group === 0 ? "#0ea5e9" : d.group === 1 ? "#8b5cf6" : "#10b981")
      .attr("fill-opacity", 0.8)
      .call(drag(simulation) as any);
    
    // Pulse animation for high value nodes
    node.filter(d => d.val > 50) // Arbitrary threshold for demo
        .append("animate")
        .attr("attributeName", "r")
        .attr("values", (d:any) => `${d.radius}; ${d.radius * 1.5}; ${d.radius}`)
        .attr("dur", "2s")
        .attr("repeatCount", "indefinite");

    node.append("title")
      .text((d: any) => JSON.stringify(data[d.id], null, 1));

    simulation.on("tick", () => {
      link
        .attr("x1", (d: any) => d.source.x)
        .attr("y1", (d: any) => d.source.y)
        .attr("x2", (d: any) => d.target.x)
        .attr("y2", (d: any) => d.target.y);

      node
        .attr("cx", (d: any) => d.x)
        .attr("cy", (d: any) => d.y);
    });

    function drag(simulation: any) {
      function dragstarted(event: any, d: any) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
      }
      
      function dragged(event: any, d: any) {
        d.fx = event.x;
        d.fy = event.y;
      }
      
      function dragended(event: any, d: any) {
        if (!event.active) simulation.alphaTarget(0);
        d.fx = null;
        d.fy = null;
      }
      
      return d3.drag()
        .on("start", dragstarted)
        .on("drag", dragged)
        .on("end", dragended);
    }

  }, [data]);

  return (
    <div className="relative w-full h-full overflow-hidden bg-[#020617] rounded-xl border border-slate-800">
       <div className="absolute top-4 left-4 z-10 bg-black/60 p-3 rounded-md border border-slate-700 backdrop-blur-md">
           <h3 className="text-city-accent font-bold uppercase tracking-widest text-xs mb-1">City Pulse Live</h3>
           <div className="flex items-center gap-2">
                <span className="relative flex h-2 w-2">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span className="text-xs text-slate-400">Network Active</span>
           </div>
       </div>
       <svg ref={svgRef} className="w-full h-full block" />
    </div>
  );
};