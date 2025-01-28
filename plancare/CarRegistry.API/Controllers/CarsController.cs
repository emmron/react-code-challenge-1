using CarRegistry.API.Services;
using CarRegistry.API.Models;
using Microsoft.AspNetCore.Mvc;

namespace CarRegistry.API.Controllers;

public class CarResponse
{
    public IEnumerable<Car> Cars { get; set; } = new List<Car>();
    public CarStats Stats { get; set; } = new();
    public string Timestamp { get; set; } = DateTime.UtcNow.AddHours(8).ToString("O"); // Perth time
}

[ApiController]
[Route("api/[controller]")]
public class CarsController : ControllerBase
{
    private readonly CarService _carService;

    public CarsController(CarService carService)
    {
        _carService = carService;
    }

    [HttpGet]
    public IActionResult GetCars([FromQuery] string? make = null, [FromQuery] string? status = null)
    {
        var cars = _carService.GetCars(make, status);
        var stats = _carService.GetCarStats(cars);
        
        return Ok(new CarResponse 
        { 
            Cars = cars,
            Stats = stats,
            Timestamp = DateTime.UtcNow.AddHours(8).ToString("O") // Perth time
        });
    }
} 