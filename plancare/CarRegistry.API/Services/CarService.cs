using System.Text.Json;
using CarRegistry.API.Models;

namespace CarRegistry.API.Services;

public class CarService
{
    private readonly List<Car> _cars;

    public CarService()
    {
        _cars = LoadCarsFromJson();
    }

    private List<Car> LoadCarsFromJson()
    {
        var jsonPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Data", "cars.json");
        if (File.Exists(jsonPath))
        {
            var jsonString = File.ReadAllText(jsonPath);
            var data = JsonSerializer.Deserialize<CarData>(jsonString);
            return data?.Cars ?? GenerateInitialCars();
        }
        return GenerateInitialCars();
    }

    private List<Car> GenerateInitialCars()
    {
        return new List<Car>
        {
            new() { Id = 1, Make = "Tesla", Model = "Model S", Year = 2023, RegistrationExpiry = DateTime.Now.AddDays(45) },
            new() { Id = 2, Make = "BMW", Model = "M3", Year = 2023, RegistrationExpiry = DateTime.Now.AddDays(-10) },
            new() { Id = 3, Make = "Porsche", Model = "911", Year = 2022, RegistrationExpiry = DateTime.Now.AddDays(15) },
            new() { Id = 4, Make = "Mercedes", Model = "AMG GT", Year = 2023, RegistrationExpiry = DateTime.Now.AddDays(90) },
            new() { Id = 5, Make = "Audi", Model = "RS7", Year = 2022, RegistrationExpiry = DateTime.Now.AddDays(30) },
            new() { Id = 6, Make = "Lamborghini", Model = "Huracan", Year = 2022, RegistrationExpiry = DateTime.Now.AddDays(120) },
            new() { Id = 7, Make = "Ferrari", Model = "F8", Year = 2023, RegistrationExpiry = DateTime.Now.AddDays(25) },
            new() { Id = 8, Make = "McLaren", Model = "720S", Year = 2022, RegistrationExpiry = DateTime.Now.AddDays(-5) }
        };
    }

    public CarStats GetCarStats(IEnumerable<Car> cars)
    {
        return new CarStats
        {
            TotalCars = cars.Count(),
            ExpiredCount = cars.Count(c => !c.IsRegistrationValid),
            ValidCount = cars.Count(c => c.IsRegistrationValid),
            ExpiringCount = cars.Count(c => c.DaysUntilExpiry is >= 0 and < 30),
            AverageYear = cars.Average(c => c.Year),
            UniqueMakes = cars.Select(c => c.Make).Distinct().Count(),
            MostCommonMake = cars.GroupBy(c => c.Make)
                                .OrderByDescending(g => g.Count())
                                .First().Key
        };
    }

    public IEnumerable<Car> GetCars(string? make = null, string? status = null)
    {
        var query = _cars.AsEnumerable();

        if (!string.IsNullOrWhiteSpace(make))
            query = query.Where(c => c.Make.Contains(make, StringComparison.OrdinalIgnoreCase));

        if (!string.IsNullOrWhiteSpace(status))
            query = query.Where(c => c.Status.Equals(status, StringComparison.OrdinalIgnoreCase));

        return query.OrderByDescending(c => c.IsRegistrationValid)
                   .ThenBy(c => c.DaysUntilExpiry);
    }
}

public class CarData
{
    public List<Car> Cars { get; set; } = new();
}

public class CarStats
{
    public int TotalCars { get; set; }
    public int ExpiredCount { get; set; }
    public int ValidCount { get; set; }
    public int ExpiringCount { get; set; }
    public double AverageYear { get; set; }
    public int UniqueMakes { get; set; }
    public string MostCommonMake { get; set; } = string.Empty;
} 