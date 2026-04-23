Feature:
        Ability to list measurements in web

        Scenario: As user I can access a list of my measurements
                Given account "MyCompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
                Given user "user2@dediez.software" with role "ROLE_USER" with id "user2" of account "mycompany" exists
                Given waste "Plastico" with id "plastico" exists
                Given property "Hotel" with id "hotel" of account "mycompany" exists
                Given container for "plastico" in "hotel" with id "container" exists
                Given measurement for container "container" and account "mycompany" from user "user1" with id "m1" exists
                Given measurement for container "container" and account "mycompany" from user "user1" with id "m2" exists
                Given measurement for container "container" and account "mycompany" from user "user2" with id "m3" exists
                Given measurement for container "container" and account "mycompany" from user "user2" with id "m4" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/measurements"
                Then the response status should be 200
                Then I see a table with 4 rows

        Scenario: As owner I can access a list of all measurements of my account
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
                Given user "user2@dediez.software" with role "ROLE_USER" with id "user2" of account "mycompany" exists
                Given waste "Plastico" with id "plastico" exists
                Given property "Hotel" with id "hotel" of account "mycompany" exists
                Given container for "plastico" in "hotel" with id "container" exists
                Given measurement for container "container" and account "mycompany" from user "user1" with id "m1" exists
                Given measurement for container "container" and account "mycompany" from user "user1" with id "m2" exists
                Given measurement for container "container" and account "mycompany" from user "user2" with id "m3" exists
                Given measurement for container "container" and account "mycompany" from user "user2" with id "m4" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/measurements"
                Then the response status should be 200
                Then I see a table with 6 rows