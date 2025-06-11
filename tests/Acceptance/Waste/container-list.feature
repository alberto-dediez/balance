Feature:
    Ability to list containers in web

    Scenario: As normal user I can't access a list of all containers
        Given user "owner@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given waste "Plastico" with id "plastico" exists
        Given property "Hotel" with id "hotel" exists
        Given container for "plastico" in "hotel" with id "111" exists
        Given container for "plastico" in "hotel" with id "222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/containers"
        Then the response status should be 403

    Scenario: As owner I can access a list of all containers of my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given waste "Plastico" with id "plastico" exists
        Given property "Hotel" with id "hotel" of account "666" exists
        Given container for "plastico" in "hotel" with id "111" exists
        Given container for "plastico" in "hotel" with id "222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/containers"
        Then the response status should be 200
        Then I see a table with 3 rows