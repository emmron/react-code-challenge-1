# Car Registry App

A modern web application for managing car registrations built with React, TypeScript, and Material-UI.

## Features

- Real-time registration status updates
- Material Design UI components
- Responsive layout
- TypeScript for type safety

## Development

1. Install dependencies:
```bash
npm install
```

2. Start the development server:
```bash
npm run dev
```

## Building for Production

```bash
npm run build
```

## Technologies Used

- React
- TypeScript
- Material-UI
- Vite
- SignalR for real-time updates

## Prerequisites

- .NET 8 SDK
- Node.js (v16 or later)
- npm (v8 or later)

## Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd car-registry
```

2. Start the backend:
```bash
cd CarRegistry.API
dotnet run
```

3. Start the frontend:
```bash
cd car-registry-client
npm install
npm run dev
```

4. Open your browser and navigate to:
- Frontend: http://localhost:3000
- Backend API: https://localhost:7186
- Swagger UI: https://localhost:7186/swagger

## Architecture

- Backend: .NET 8 Web API with SignalR for real-time updates
- Frontend: React with TypeScript, Material-UI, and Vite
- Real-time updates using SignalR
- Mock data for demonstration purposes

## License

MIT 