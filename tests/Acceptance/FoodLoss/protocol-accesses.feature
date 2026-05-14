Feature:
    Ability to view protocol employee accesses

    Scenario: As owner I can see the accesses list for a protocol
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given protocol "Protocol 1" with id "1111" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/protocols/1111/accesses"
        Then the response status should be 200

    Scenario: As owner I see all employees with and without access
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given protocol "Protocol 1" with id "1111" of account "mycompany" exists
        Given employee "Alice" with id "emp-1" of account "mycompany" exists
        Given employee "Bob" with id "emp-2" of account "mycompany" exists
        Given protocol access for employee "emp-1" of protocol "1111" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/protocols/1111/accesses"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: As normal user I cannot see the accesses list
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given protocol "Protocol 1" with id "1111" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/protocols/1111/accesses"
        Then the response status should be 403

    Scenario: As admin I cannot see the accesses list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/protocols/1111/accesses"
        Then the response status should be 403
