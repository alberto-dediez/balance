Feature:
    Ability to list users in web

    Scenario: As admin I can access a list of all users
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a table with 5 rows

    Scenario: As normal user I can't access a list of all users
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 403

    Scenario: As owner I can access a list of users of my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists  
        Given user "user1@dediez.software" with role "ROLE_USER" of account "666" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then the response status should be 200
        Then I see a table with 4 rows