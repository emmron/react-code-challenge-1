import React, { useEffect, useState } from "react";
import {
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Typography,
  Box,
  Alert,
  CircularProgress,
  Chip
} from "@mui/material";
import { HubConnectionBuilder } from "@microsoft/signalr";
import RefreshIcon from "@mui/icons-material/Refresh";
import ErrorOutlineIcon from "@mui/icons-material/ErrorOutline";
import CheckCircleOutlineIcon from "@mui/icons-material/CheckCircleOutline";
import WarningAmberIcon from "@mui/icons-material/WarningAmber";

interface RegistrationStatus {
  id: string;
  status: string;
  message: string;
  timestamp: string;
}

const RegistrationStatus: React.FC = () => {
  const [registrations, setRegistrations] = useState<RegistrationStatus[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const connection = new HubConnectionBuilder()
      .withUrl("http://localhost:5074/registrationHub")
      .withAutomaticReconnect()
      .build();

    const startConnection = async () => {
      try {
        await connection.start();
        console.log("Connected to SignalR hub");

        // Subscribe to registration updates
        connection.on("ReceiveRegistrationUpdate", (registration: RegistrationStatus) => {
          setRegistrations(prev => [...prev, registration]);
        });

        // Fetch initial registrations
        const response = await fetch("http://localhost:5074/api/registrations");
        if (!response.ok) {
          throw new Error("Failed to fetch registrations");
        }
        const data = await response.json();
        setRegistrations(data);
        setLoading(false);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to connect to server");
        setLoading(false);
      }
    };

    startConnection();

    return () => {
      connection.stop();
    };
  }, []);

  const getStatusIcon = (status: string): React.ReactElement => {
    switch (status.toLowerCase()) {
      case "completed":
        return <CheckCircleOutlineIcon color="success" />;
      case "failed":
        return <ErrorOutlineIcon color="error" />;
      case "processing":
        return <RefreshIcon color="primary" />;
      default:
        return <WarningAmberIcon color="warning" />;
    }
  };

  const getStatusColor = (status: string): "success" | "error" | "primary" | "warning" => {
    switch (status.toLowerCase()) {
      case "completed":
        return "success";
      case "failed":
        return "error";
      case "processing":
        return "primary";
      default:
        return "warning";
    }
  };

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="200px">
        <CircularProgress />
      </Box>
    );
  }

  if (error) {
    return (
      <Alert severity="error" sx={{ mt: 2 }}>
        {error}
      </Alert>
    );
  }

  return (
    <Box sx={{ mt: 4 }}>
      <Typography variant="h5" gutterBottom>
        Registration Status
      </Typography>
      <TableContainer component={Paper}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>ID</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Message</TableCell>
              <TableCell>Timestamp</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {registrations.map((reg) => (
              <TableRow key={reg.id}>
                <TableCell>{reg.id}</TableCell>
                <TableCell>
                  <Chip
                    icon={getStatusIcon(reg.status)}
                    label={reg.status}
                    color={getStatusColor(reg.status)}
                    size="small"
                  />
                </TableCell>
                <TableCell>{reg.message}</TableCell>
                <TableCell>{new Date(reg.timestamp).toLocaleString()}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    </Box>
  );
};

export default RegistrationStatus; 