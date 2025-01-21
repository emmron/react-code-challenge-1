using CarRegistry.API.Models;
using System.Text.Json;

namespace CarRegistry.API.Services;

public class CarService
{
    private readonly List<Car> _cars;

    public CarService(IWebHostEnvironment environment)
    {
        var jsonPath = Path.Combine(environment.ContentRootPath, "Data", "cars.json");
        var jsonContent = File.ReadAllText(jsonPath);
        var data = JsonSerializer.Deserialize<CarData>(jsonContent);
        _cars = data?.Cars ?? new List<Car>();
    }

    public IEnumerable<Car> GetCars(string? make = null)
    {
        return make == null 
            ? _cars 
            : _cars.Where(c => c.Make.Equals(make, StringComparison.OrdinalIgnoreCase));
    }
}

public class CarData
{
    public List<Car> Cars { get; set; } = new();
} 