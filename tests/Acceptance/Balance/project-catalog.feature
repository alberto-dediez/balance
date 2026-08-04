Feature:
    Ability for owners to browse the project catalog

    Scenario: As an owner I can access the project catalog
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given project "Item 2" with id "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: As an owner I can filter the projects adhered to my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given project "Item 2" with id "2222" exists
        Given project "1111" is adhered to account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects"
        When I submit a form with "selected:1" and button "list_projects[filter]"
        Then the response status should be 200
        Then I see a text "Item 1"
        Then I do not see a text "Item 2"

    Scenario: As an owner I can filter the projects not adhered to my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given project "Item 2" with id "2222" exists
        Given project "1111" is adhered to account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects"
        When I submit a form with "selected:0" and button "list_projects[filter]"
        Then the response status should be 200
        Then I see a text "Item 2"
        Then I do not see a text "Item 1"

    Scenario: As a normal user I can't access the project catalog
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/projects"
        Then the response status should be 403
