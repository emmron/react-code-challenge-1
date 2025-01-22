using CarRegistry.API.Services;
using CarRegistry.API.Models;
using Microsoft.AspNetCore.Mvc;

namespace CarRegistry.API.Controllers;

public class CarResponse
{
    public IEnumerable<Car> Cars { get; set; } = new List<Car>();
    public CarStats Stats { get; set; } = new();
    public string Timestamp { get; set; } = DateTime.UtcNow.ToString("O");
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
    public IActionResult GetCars([FromQuery] string? make = null)
    {
        var cars = _carService.GetCars(make);
        var stats = _carService.GetCarStats(cars);
        
        return Ok(new CarResponse 
        { 
            Cars = cars,
            Stats = stats,
            Timestamp = DateTime.UtcNow.ToString("O")
        });
    }
} 