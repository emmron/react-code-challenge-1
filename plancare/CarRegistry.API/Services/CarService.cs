using System.Text.Json;
using System.Net.Http;
using CarRegistry.API.Models;

namespace CarRegistry.API.Services;

public class CarService
{
    private readonly List<Car> _cars;
    private readonly HttpClient _httpClient;
    private const string CARSALES_API_URL = "https://www.carsales.com.au/api/";  // Example API endpoint

    public CarService(IHttpClientFactory httpClientFactory)
    {
        _httpClient = httpClientFactory.CreateClient();
        _cars = LoadCarsFromJson();
        EnrichWithRealTimeData().Wait(); // Note: In production, make this async
    }

    private async Task EnrichWithRealTimeData()
    {
        try
        {
            // In a real implementation, you would:
            // 1. Use proper API authentication
            // 2. Handle rate limiting
            // 3. Cache responses
            // 4. Use proper error handling
            // var response = await _httpClient.GetFromJsonAsync<CarApiResponse>(CARSALES_API_URL);
            
            // For now, we'll use our generated data
            var generatedCars = GenerateInitialCars();
            _cars.AddRange(generatedCars);
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Failed to fetch real-time data: {ex.Message}");
            // Fallback to generated data
            var generatedCars = GenerateInitialCars();
            _cars.AddRange(generatedCars);
        }
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
        var cars = new List<Car>();
        var random = new Random(42);
        var makes = new[] { "Tesla", "BMW", "Porsche", "Mercedes", "Audi", "Lamborghini", "Ferrari", "McLaren", "Bugatti", "Rolls-Royce", "Bentley", "Aston Martin", "Maserati", "Koenigsegg" };
        var models = new Dictionary<string, (string[] Models, decimal BasePrice)>
        {
            ["Tesla"] = (new[] { "Model S Plaid", "Model X Plaid", "Roadster", "Cybertruck" }, 135000),
            ["BMW"] = (new[] { "M8 Competition", "M5 CS", "XM Label Red", "M4 CSL" }, 165000),
            ["Porsche"] = (new[] { "911 GT3 RS", "918 Spyder", "Taycan Turbo S", "GT2 RS" }, 225000),
            ["Mercedes"] = (new[] { "AMG GT Black Series", "AMG ONE", "SL 63 AMG", "G 63 AMG" }, 325000),
            ["Audi"] = (new[] { "RS e-tron GT", "R8 V10", "RS6 Avant", "RS Q8" }, 145000),
            ["Lamborghini"] = (new[] { "Revuelto", "Huracan STO", "Urus Performante", "Countach LPI" }, 498000),
            ["Ferrari"] = (new[] { "SF90 Stradale", "LaFerrari", "F8 Tributo", "812 Competizione" }, 625000),
            ["McLaren"] = (new[] { "765LT Spider", "P1", "Speedtail", "Senna" }, 520000),
            ["Bugatti"] = (new[] { "Chiron Super Sport", "Mistral", "Divo", "La Voiture Noire" }, 3900000),
            ["Rolls-Royce"] = (new[] { "Phantom", "Cullinan", "Ghost Black Badge", "Boat Tail" }, 475000),
            ["Bentley"] = (new[] { "Continental GT Speed", "Flying Spur", "Bentayga EWB", "Batur" }, 335000),
            ["Aston Martin"] = (new[] { "DBS 770", "Valkyrie", "DBX707", "Vantage F1" }, 425000),
            ["Maserati"] = (new[] { "MC20", "GranTurismo", "Grecale Trofeo", "Levante" }, 285000),
            ["Koenigsegg"] = (new[] { "Jesko", "Gemera", "Regera", "CC850" }, 2900000)
        };

        // Generate cars with Perth locations
        var perthSuburbs = new[] { "Perth CBD", "Northbridge", "Subiaco", "Fremantle", "Cottesloe", "Scarborough", "Claremont", "South Perth" };
        var dealerships = new[] { "Barbagallo", "AutoClassic", "Perth City", "Diesel Motors", "DVG", "John Hughes" };

        for (int i = 1; i <= 2000; i++)
        {
            var make = makes[random.Next(makes.Length)];
            var (modelOptions, basePrice) = models[make];
            var model = modelOptions[random.Next(modelOptions.Length)];
            
            var priceVariation = (decimal)(0.8 + (random.NextDouble() * 0.4));
            var price = basePrice * priceVariation;
            
            var daysOffset = random.Next(-30, 366);
            var registrationExpiry = DateTime.Now.AddDays(daysOffset);
            
            var suburb = perthSuburbs[random.Next(perthSuburbs.Length)];
            var dealership = dealerships[random.Next(dealerships.Length)];
            var vin = $"{make[0]}{model[0]}{random.Next(100000, 999999)}{DateTime.Now.Year % 100}{i:D4}";

            cars.Add(new Car
            {
                Id = i,
                Make = make,
                Model = model,
                Year = random.Next(2021, 2025),
                Price = Math.Round(price, 0),
                RegistrationExpiry = registrationExpiry,
                VIN = vin,
                Location = $"{dealership} - {suburb}, WA",
                LastUpdated = DateTime.UtcNow.AddHours(8) // Perth time (UTC+8)
            });
        }

        return cars;
    }

    public CarStats GetCarStats(IEnumerable<Car> cars)
    {
        var carsList = cars.ToList();
        if (!carsList.Any()) return new CarStats();

        var byLocation = carsList.GroupBy(c => c.Location)
                                .OrderByDescending(g => g.Count())
                                .First();

        return new CarStats
        {
            TotalCars = carsList.Count,
            ExpiredCount = carsList.Count(c => !c.IsRegistrationValid),
            ValidCount = carsList.Count(c => c.IsRegistrationValid),
            ExpiringCount = carsList.Count(c => c.DaysUntilExpiry is >= 0 and < 30),
            AverageYear = carsList.Average(c => c.Year),
            UniqueMakes = carsList.Select(c => c.Make).Distinct().Count(),
            MostCommonMake = carsList.GroupBy(c => c.Make)
                                   .OrderByDescending(g => g.Count())
                                   .First().Key,
            AveragePrice = (double)carsList.Average(c => c.Price),
            MostExpensiveCar = carsList.OrderByDescending(c => c.Price).First().DisplayName,
            MostPopularLocation = $"{byLocation.Key} ({byLocation.Count()} vehicles)",
            TotalValue = (double)carsList.Sum(c => c.Price),
            LastUpdated = DateTime.UtcNow.AddHours(8).ToString("F") // Perth time
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
    public double AveragePrice { get; set; }
    public string MostExpensiveCar { get; set; } = string.Empty;
    public string MostPopularLocation { get; set; } = string.Empty;
    public double TotalValue { get; set; }
    public string LastUpdated { get; set; } = string.Empty;
} 