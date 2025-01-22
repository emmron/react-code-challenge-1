using Microsoft.AspNetCore.SignalR;
using CarRegistry.API.Hubs;
using CarRegistry.API.Models;

namespace CarRegistry.API.Services;

public class CarService : BackgroundService
{
    private readonly IHubContext<CarHub> _hubContext;
    private readonly List<Car> _cars;

    public CarService(IHubContext<CarHub> hubContext)
    {
        _hubContext = hubContext;
        _cars = GenerateInitialCars();
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

    public IEnumerable<Car> GetCars(string? make = null)
    {
        if (string.IsNullOrWhiteSpace(make))
            return _cars;
        
        return _cars.Where(c => c.Make.Contains(make, StringComparison.OrdinalIgnoreCase));
    }

    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        using var timer = new PeriodicTimer(TimeSpan.FromSeconds(10));

        while (await timer.WaitForNextTickAsync(stoppingToken))
        {
            var expiredCount = _cars.Count(c => c.RegistrationExpiry < DateTime.Now);
            var validCount = _cars.Count(c => c.RegistrationExpiry >= DateTime.Now);
            
            var update = new RegistrationUpdate
            {
                Timestamp = DateTime.Now.ToString("O"),
                ExpiredCount = expiredCount,
                ValidCount = validCount,
                TotalCars = _cars.Count
            };

            await _hubContext.Clients.All.SendAsync("ReceiveRegistrationUpdate", update, stoppingToken);
        }
    }
}

public class CarData
{
    public List<Car> Cars { get; set; } = new();
} 