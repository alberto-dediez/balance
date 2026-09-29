Feature:
    I want to make sure that the software allows to list the materiality sectors

    Scenario: As an admin, I can list the materiality sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        Given materiality sector "Eventos" with id "222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors"
        Then the response status should be 200
        Then I see a table with 4 rows
        Then I see a text "Hoteles"
        Then I see a text "Eventos"

    Scenario: As an admin, I can filter the materiality sectors by name
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        Given materiality sector "Eventos" with id "222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors"
        When I filter by "name" with "Hotel"
        Then the response status should be 200
        Then I see a text "Hoteles"
        Then I do not see a text "Eventos"

    Scenario: As an admin, I see a hint when there are no materiality sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors"
        Then the response status should be 200
        Then I see a text "No hay sectores. Registra el primero."

    Scenario: As an admin, I see a no results message when the filter matches no materiality sector
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors"
        When I filter by "name" with "Ferias"
        Then the response status should be 200
        Then I see a text "No hay sectores que coincidan con los filtros."
        Then I do not see a text "No hay sectores. Registra el primero."

    Scenario: As an admin, I see the materiality sectors in the menu
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "/es/admin/materiality-sectors"

    Scenario: As normal user I can't list the materiality sectors
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-sectors"
        Then the response status should be 403
