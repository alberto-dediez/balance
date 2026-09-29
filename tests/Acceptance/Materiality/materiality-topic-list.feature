Feature:
    I want to make sure that the software allows to list the materiality topics

    Scenario: As an admin, I can list the materiality topics
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality topic "Consumo de gas" with id "222" in scope "scope_1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        Then the response status should be 200
        Then I see a table with 4 rows
        Then I see a text "Lavandería externa"
        Then I see a text "Consumo de gas"

    Scenario: As normal user I can't list the materiality topics
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-topics"
        Then the response status should be 403

    Scenario: As an admin, I can filter the materiality topics by scope
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality topic "Consumo de gas" with id "222" in scope "scope_1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        When I filter by "scope" with "scope_1"
        Then the response status should be 200
        Then I see a text "Consumo de gas"
        Then I do not see a text "Lavandería externa"
        Then I see a text "<b>Alcance 1</b>"

    Scenario: As an admin, I can filter the materiality topics by direction
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality topic "Consumo de gas" with id "222" in scope "scope_1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        When I filter by "direction" with "upstream"
        Then the response status should be 200
        Then I see a text "Lavandería externa"
        Then I do not see a text "Consumo de gas"
        Then I see a text "<b>Aguas arriba</b>"

    Scenario: As an admin, I see a hint when there are no materiality topics
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        Then the response status should be 200
        Then I see a text "No hay categorías. Siembra los maestros estándar o registra una nueva."

    Scenario: As an admin, I see a no results message when the filter matches no materiality topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        When I filter by "name" with "Ferias"
        Then the response status should be 200
        Then I see a text "No hay categorías que coincidan con los filtros."
        Then I do not see a text "No hay categorías. Siembra los maestros estándar o registra una nueva."

    Scenario: As an admin, I see the materiality group in the menu
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "/es/admin/materiality-topics"
        Then I see a text "/es/admin/materiality-questions"

    Scenario: As an owner, I don't see the materiality group in the menu
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I do not see a text "/es/admin/materiality-topics"
        Then I do not see a text "/es/admin/materiality-questions"
