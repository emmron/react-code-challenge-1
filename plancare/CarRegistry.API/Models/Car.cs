namespace CarRegistry.API.Models;

public class Car
{
    public int Id { get; set; }
    public string Make { get; set; } = string.Empty;
    public string Model { get; set; } = string.Empty;
    public int Year { get; set; }
    public decimal Price { get; set; }
    public DateTime RegistrationExpiry { get; set; }
    public string VIN { get; set; } = string.Empty;
    public string Location { get; set; } = string.Empty;
    public DateTime LastUpdated { get; set; }

    // Computed properties
    public bool IsRegistrationValid => RegistrationExpiry > DateTime.Now;
    public int DaysUntilExpiry => (int)(RegistrationExpiry - DateTime.Now).TotalDays;
    public string Status => DaysUntilExpiry switch
    {
        < 0 => "Expired",
        < 30 => "Expiring Soon",
        _ => "Valid"
    };
    public string StatusColor => Status switch
    {
        "Expired" => "#ef4444",
        "Expiring Soon" => "#f97316",
        _ => "#22c55e"
    };
    public string DisplayName => $"{Make} {Model} ({Location}) - ${Price:N0}";
    public int Age => DateTime.Now.Year - Year;
    public double DepreciationPercent => Math.Min(100, Age * 12); // 12% per year
    public decimal CurrentValue => Price * (1 - ((decimal)DepreciationPercent / 100));
    public string ExpiryCountdown => DaysUntilExpiry switch
    {
        < 0 => $"Expired {Math.Abs(DaysUntilExpiry)} days ago",
        0 => "Expires today",
        1 => "Expires tomorrow",
        _ => $"Expires in {DaysUntilExpiry} days"
    };
} 