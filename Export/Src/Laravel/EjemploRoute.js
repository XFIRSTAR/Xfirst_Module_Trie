[
  {
    "method": "GET",
    "uri": "/usuarios",
    "action": ["App\\Http\\Controllers\\UsuarioController", "index"],
    "middleware": ["auth"]
  },
  {
    "method": "POST",
    "uri": "/usuarios",
    "action": ["App\\Http\\Controllers\\UsuarioController", "store"]
  }
]
