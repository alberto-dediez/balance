Feature:
    The admins see the usage of the AI assistant

    Scenario: An admin sees the assistant usage of the period
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "admin@dediez.software" has spent "1.250000" USD on the assistant today
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/assistant/usage"
        Then the response status should be 200
        Then I see a text "Consumo del asistente"
        Then I see a text "Semana"
        Then I see a text "Coste (USD)"
        Then I see a text "1,25 $"
        Then I see a text "admin@dediez.software"

    Scenario: The usage of another period is empty
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "admin@dediez.software" has spent "1.250000" USD on the assistant today
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/assistant/usage?period=month&date=2020-01-01"
        Then the response status should be 200
        Then I see a text "Sin actividad en el periodo"

    Scenario: An owner cannot see the assistant usage
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/assistant/usage"
        Then the response status should be 403

    Scenario: The menu of an admin has the assistant usage
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then I see a link with name "Consumo del asistente"
