namespace CarRegistry.API.Models;

public class Car
{
    public int Id { get; set; }
    public string Make { get; set; } = string.Empty;
    public string Model { get; set; } = string.Empty;
    public int Year { get; set; }
    public DateTime RegistrationExpiry { get; set; }
    public bool IsRegistrationValid => RegistrationExpiry > DateTime.UtcNow;
} 