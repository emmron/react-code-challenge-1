using CarRegistry.API.Hubs;
using CarRegistry.API.Services;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddControllers();
// Learn more about configuring Swagger/OpenAPI at https://aka.ms/aspnetcore/swashbuckle
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

// Add SignalR
builder.Services.AddSignalR();

// Add CORS
builder.Services.AddCors(options =>
{
    options.AddDefaultPolicy(builder =>
    {
        builder
            .WithOrigins(
                "http://localhost:3000", // Development
                "http://localhost:4173", // Vite preview
                "https://your-production-domain.com" // Add your production domain
            )
            .AllowAnyMethod()
            .AllowAnyHeader()
            .AllowCredentials();
    });
});

// Add CarService as singleton
builder.Services.AddSingleton<CarService>();
builder.Services.AddHostedService(sp => sp.GetRequiredService<CarService>());

var app = builder.Build();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

app.UseHttpsRedirection();

// Use CORS before routing
app.UseCors();

app.UseRouting();
app.UseAuthorization();

app.MapControllers();
app.MapHub<CarHub>("/carHub");

app.Run();
