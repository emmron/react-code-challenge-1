using CarRegistry.API.Services;
using Microsoft.AspNetCore.Mvc;

namespace CarRegistry.API.Controllers;

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
        return Ok(cars);
    }
} 