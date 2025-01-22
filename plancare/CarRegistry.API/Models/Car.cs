namespace CarRegistry.API.Models;

public class Car
{
    public int Id { get; set; }
    public string Make { get; set; } = string.Empty;
    public string Model { get; set; } = string.Empty;
    public int Year { get; set; }
    public DateTime RegistrationExpiry { get; set; }
    public decimal Price { get; set; }
    public string VIN { get; set; } = string.Empty;

    // HEAVY LIFTING COMPUTED PROPERTIES 💪
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
    
    // More computed properties to reduce frontend work
    public string DisplayName => $"{Year} {Make} {Model}";
    public string PriceDisplay => Price.ToString("C0");
    public string FormattedExpiryDate => RegistrationExpiry.ToString("MMM dd, yyyy");
    public int Age => DateTime.Now.Year - Year;
    public string AgeDisplay => $"{Age} year{(Age == 1 ? "" : "s")} old";
    public double DepreciationPercent => Math.Min(100, Age * 12); // Rough estimate: 12% per year
    public decimal CurrentValue => Price * (1 - ((decimal)DepreciationPercent / 100));
    public string CurrentValueDisplay => CurrentValue.ToString("C0");
    public string ExpiryCountdown => DaysUntilExpiry switch
    {
        < 0 => $"Expired {Math.Abs(DaysUntilExpiry)} days ago",
        0 => "Expires today",
        1 => "Expires tomorrow",
        _ => $"Expires in {DaysUntilExpiry} days"
    };
    public string Severity => DaysUntilExpiry switch
    {
        < -30 => "CRITICAL",
        < 0 => "HIGH",
        < 30 => "MEDIUM",
        < 90 => "LOW",
        _ => "NONE"
    };
    public bool IsLuxury => Price > 50000;
} 