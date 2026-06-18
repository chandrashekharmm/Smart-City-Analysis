import { GoogleGenAI, Type } from "@google/genai";
import { DashboardConfig, DatasetMetadata, WidgetType } from "../types";

const apiKey = process.env.API_KEY || '';
const ai = new GoogleGenAI({ apiKey });

export const generateDashboardConfig = async (metadata: DatasetMetadata): Promise<DashboardConfig> => {
  const model = "gemini-2.5-flash";
  
  // Prepare context
  const columnDesc = Object.entries(metadata.stats)
    .map(([key, stat]) => `${key} (${stat.type}): range [${stat.min}, ${stat.max}], unique ${stat.uniqueValues}`)
    .join('\n');

  const sampleData = JSON.stringify(metadata.raw.slice(0, 5));

  const prompt = `
    You are a Smart City Big Data Architect. 
    Analyze the following dataset metadata and sample rows.
    Dataset Columns & Stats:
    ${columnDesc}
    
    Sample Data:
    ${sampleData}

    Generate a comprehensive Dashboard Configuration for a "Smart City Command Hub".
    Select the most relevant visualizations to uncover insights about urban efficiency, resources, or demographics.
    
    Requirements:
    1. Create a title for the dashboard.
    2. Generate 6-8 widgets.
    3. Provide AI-driven insights (summary, anomalies, recommendations).
    4. IMPORTANT: Use EXACT keys from the dataset for "dataKeyX" and "dataKeyY". Do not invent keys.
    5. Use "SCATTER" type for correlation charts (e.g. Traffic vs Air Quality).
    6. Use "KPI" type for single aggregated metrics (e.g. Active Sensors, Average Temp).
  `;

  try {
    const response = await ai.models.generateContent({
      model,
      contents: prompt,
      config: {
        responseMimeType: "application/json",
        responseSchema: {
          type: Type.OBJECT,
          properties: {
            title: { type: Type.STRING },
            widgets: {
              type: Type.ARRAY,
              items: {
                type: Type.OBJECT,
                properties: {
                  id: { type: Type.STRING },
                  title: { type: Type.STRING },
                  type: { type: Type.STRING, enum: Object.values(WidgetType) },
                  dataKeyX: { type: Type.STRING },
                  dataKeyY: { type: Type.STRING },
                  description: { type: Type.STRING },
                  gridArea: { type: Type.STRING }
                },
                required: ["id", "title", "type", "dataKeyX", "dataKeyY"]
              }
            },
            insights: {
              type: Type.OBJECT,
              properties: {
                summary: { type: Type.STRING },
                anomalies: { type: Type.ARRAY, items: { type: Type.STRING } },
                recommendations: { type: Type.ARRAY, items: { type: Type.STRING } }
              },
              required: ["summary", "anomalies", "recommendations"]
            }
          },
          required: ["title", "widgets", "insights"]
        }
      }
    });

    const text = response.text;
    if (!text) throw new Error("No response from AI");
    
    return JSON.parse(text) as DashboardConfig;

  } catch (error) {
    console.error("Gemini API Error:", error);
    // Fallback mock config if API fails (simulated for robustness)
    return {
      title: "Data Analysis (Offline Mode)",
      widgets: [],
      insights: {
        summary: "Could not connect to AI. Please check your API key.",
        anomalies: [],
        recommendations: []
      }
    };
  }
};

export const askCityAssistant = async (query: string, contextData: string) => {
    const model = "gemini-2.5-flash";
    
    const systemPrompt = `
      You are a Smart City AI Assistant. 
      Context Data Summary: ${contextData}
      
      Instructions:
      1. Keep answers brief, professional, and actionable.
      2. IF the user asks for a sample CSV format or data structure, provide the following standard format EXACTLY:
         
         \`\`\`csv
         Timestamp,District,Energy_Usage_kWh,Traffic_Density,Air_Quality_Index,Temperature_C,Active_Sensors
         2023-11-01 08:00,Downtown,450.2,85,42,18,120
         2023-11-01 09:00,Industrial,1200.5,60,85,22,85
         \`\`\`
         
      3. Base your answers on the provided context data if possible.
    `;

    try {
        const response = await ai.models.generateContent({
            model,
            contents: `${systemPrompt}\n\nUser Query: ${query}`,
        });
        return response.text;
    } catch (e) {
        return "System offline. Unable to process query.";
    }
}