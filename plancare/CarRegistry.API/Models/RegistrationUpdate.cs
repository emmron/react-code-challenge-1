namespace CarRegistry.API.Models;

public class RegistrationUpdate
{
    public string Timestamp { get; set; } = string.Empty;
    public int ExpiredCount { get; set; }
    public int ValidCount { get; set; }
    public int TotalCars { get; set; }
} 