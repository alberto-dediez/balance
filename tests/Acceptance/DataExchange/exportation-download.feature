Feature:
    I want to make sure that the system allows downloading a generated exportation

    Scenario: As an admin, I can download a completed exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "users" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/users/export/333/download"
        Then the response status should be 200

    Scenario: As a superadmin, I can download a completed exportation
        Given user "root@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given a completed exportation with id "333" of type "users" from account "mycompany" exists
        When I am logged as "root@dediez.software"
        When I go to "/es/users/export/333/download"
        Then the response status should be 200

    Scenario: As an owner, I can download a completed exportation of my account
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given a completed exportation with id "333" of type "users" from account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/users/export/333/download"
        Then the response status should be 200

    Scenario: As an owner, I cannot download an exportation of another account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given a completed exportation with id "444" of type "users" from account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/users/export/444/download"
        Then the response status should be 403

    Scenario: As a normal user, I cannot download an exportation
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given a completed exportation with id "333" of type "users" from account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/users/export/333/download"
        Then the response status should be 403

    Scenario: Downloading an expired exportation returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an expired exportation with id "555" of type "users" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/users/export/555/download"
        Then the response status should be 404

    Scenario: Downloading a non-existent exportation returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/users/export/does-not-exist/download"
        Then the response status should be 404

    Scenario: Downloading from an unknown export type returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/unknown/export/333/download"
        Then the response status should be 404
