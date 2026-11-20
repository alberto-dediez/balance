Feature:
    Ability for admins to list the contact requests

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given contact request with id "CR-PENDING" of account "666" exists
        Given contact request with id "CR-MANAGED" of account "666" exists
        Given contact request "CR-MANAGED" is managed
        Given contact request with id "CR-DISCARDED" of account "666" exists

    Scenario: As an admin I only see the pending contact requests by default
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests/CR-DISCARDED/discard" with csrf token "contact_request_discard"
        When I go to "/es/admin/contact-requests"
        Then the response status should be 200
        Then I see a form field "[resolved]" with value "0"
        Then I see a text "CR-PENDING@example.com"
        Then I do not see a text "CR-MANAGED@example.com"
        Then I do not see a text "CR-DISCARDED@example.com"

    Scenario: As an admin I can list the finished contact requests
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests/CR-DISCARDED/discard" with csrf token "contact_request_discard"
        When I go to "/es/admin/contact-requests"
        When I filter the admin contact requests list by finished "yes"
        Then the response status should be 200
        Then I do not see a text "CR-PENDING@example.com"
        Then I see a text "CR-MANAGED@example.com"
        Then I see a text "CR-DISCARDED@example.com"

    Scenario: As an admin I can list all the contact requests
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests"
        When I filter the admin contact requests list by finished "all"
        Then the response status should be 200
        Then I see a text "CR-PENDING@example.com"
        Then I see a text "CR-MANAGED@example.com"
        Then I see a text "CR-DISCARDED@example.com"

    Scenario: As an owner I can't list the contact requests
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/contact-requests"
        Then the response status should be 403
