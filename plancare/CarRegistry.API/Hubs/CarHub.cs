using Microsoft.AspNetCore.SignalR;
using CarRegistry.API.Models;

namespace CarRegistry.API.Hubs;

public class CarHub : Hub
{
    public override async Task OnConnectedAsync()
    {
        await Clients.Caller.SendAsync("Connected", $"Connected successfully. Connection ID: {Context.ConnectionId}");
        await base.OnConnectedAsync();
    }

    public async Task SendRegistrationUpdate(RegistrationUpdate update)
    {
        await Clients.All.SendAsync("ReceiveRegistrationUpdate", update);
    }
} 