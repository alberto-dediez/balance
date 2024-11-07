Feature:
    In order to prove that the current Behat testing is working

    Scenario: Health check status is OK
        When I send a GET request to "/api/health-check"
        Then the response JSON should be equal to:
        """
        {
            "status": "ok"
        }
        """
        And the status code should be 200

    Scenario: Command health:check is executed without errors
        Given I am the system
        When command "health:check" is executed
        Then I expect status code "0"
        Then I want to see "ok" in command output

    Scenario: Route /health-check is loaded without errors
        When I go to "/health-check"
        Then the response status should be "200"
        Then I see a text "ok"