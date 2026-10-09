import uuid
from locust import HttpUser, task, between


class ApiUser(HttpUser):

    wait_time = between(1, 3)

    HEADERS = {"Accept": "application/json"}

    @task(5)
    def listar_usuarios(self):
        with self.client.get("/api/users", headers=self.HEADERS,
                             name="GET /api/users", catch_response=True) as r:
            self._validar_get(r)

    @task(3)
    def listar_correos(self):
        with self.client.get("/api/users/emails", headers=self.HEADERS,
                             name="GET /api/users/emails", catch_response=True) as r:
            self._validar_get(r)

    @task(2)
    def listar_mayores_de_20(self):
        with self.client.get("/api/users/over-twenty", headers=self.HEADERS,
                             name="GET /api/users/over-twenty", catch_response=True) as r:
            self._validar_get(r)

    @task(1)
    def alta_en_lote(self):
        usuarios = [
            {
                "name": f"Load User {i}",
                "email": f"{uuid.uuid4().hex}@loadtest.com",
                "birth_date": "1995-05-15",
            }
            for i in range(3)
        ]
        with self.client.post("/api/users/bulk", json={"users": usuarios},
                              headers=self.HEADERS,
                              name="POST /api/users/bulk", catch_response=True) as r:
            if r.status_code != 201:
                r.failure(f"Esperado 201, recibido {r.status_code}: {r.text[:200]}")
            else:
                r.success()

    # Validación
    def _validar_get(self, r):
        if r.status_code != 200:
            r.failure(f"HTTP {r.status_code}")
            return
        try:
            body = r.json()
        except ValueError:
            r.failure("La respuesta no es un JSON válido")
            return
        if "data" not in body:
            r.failure("Falta la clave 'data' en el JSON")
        else:
            r.success()