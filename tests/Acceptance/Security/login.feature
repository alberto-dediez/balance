Feature:
        Access to api login

        Scenario: Api login returns token
                Given user "user@dediez.software" with role "ROLE_USER" exists
                When I send a POST request to "/api/login" with body:
                """
                {
                        "username": "user@dediez.software",
                        "password": "apassword"
                }
                """
                Then the status code should be 200
                And the response JSON should be similar to:
                """
                {
                        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NDk1NDQ5MzEsImV4cCI6MTc0OTU0ODUzMSwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6ImFsYmVydG9AZGVkaWV6LnNvZnR3YXJlIn0.Ur1oHFNlP0dRxtKOr8uwpwgoeT5YFM-uGQUVvpUi0_MRWzwFjJXRYk2V8vntNEUmpkSmWkCiyjRuNmGOMFCSlk_JDMgzBhYMGUoFdr0ndVS-nDdHCuqkknhG54oNfb2nyb9rdNXr3zYQ4TYQiP3qnHkz09184ZM3bnLy5oUIQnmnGT5l6V340AAzjPxW_q8-NdSymhFpxFEu5daQ2rljvkpz8NGYoAUX6Y7tUOybyXAg9heEtHUtSQkLFKNikWLi4BEU4qrceBsEaCMgtxzYefFuv1n3KhjRmV8p_7HR8wMDYXq5UvsRnJEmkxTAHo6YBwuIL4AS0qNddM_ZPTzgFg"
                }
                """

        Scenario: Given an expired token, we need to return a known error
                Given user "user@dediez.software" with role "ROLE_USER" exists
                Given I have an expired token for "user@dediez.software"
                When I send a GET request to "/api/users/me"
                Then the status code should be 401