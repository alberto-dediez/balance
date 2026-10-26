Feature:
    In order to prove that the current Behat testing is working

    Scenario: Health check status is OK
        When I send a GET request to "/api/health-check"
        Then the response JSON should be equal to:
        """
        {
            "status": "ok",
            "checks": {
                "database": "ok",
                "queue": "ok",
                "in-memory": "ok"
            }
        }
        """
        And the status code should be 200

    Scenario: Health check reports a failing component
        Given the health indicator "in-memory" is failing
        When I send a GET request to "/api/health-check"
        Then the response JSON should be equal to:
        """
        {
            "status": "error",
            "checks": {
                "database": "ok",
                "queue": "ok",
                "in-memory": "error"
            }
        }
        """
        And the status code should be 503

    Scenario: Command health:check is executed without errors
        Given I am the system
        When command "health:check" is executed
        Then I expect status code "0"
        Then I want to see "ok" in command output
        Then I want to see "database: ok" in command output
        Then I want to see "queue: ok" in command output

    Scenario: Command health:check fails when a component is failing
        Given I am the system
        And the health indicator "in-memory" is failing
        When command "health:check" is executed
        Then I expect status code "1"
        Then I want to see "status: error" in command output

    Scenario: Route /health-check is loaded without errors
        When I go to "/es/health-check"
        Then the response status should be "200"
        Then I see a text "ok"

    Scenario: Route /health-check reports a failing component
        Given the health indicator "in-memory" is failing
        When I go to "/es/health-check"
        Then the response status should be "503"
        Then I see a text "error"
