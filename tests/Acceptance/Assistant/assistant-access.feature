Feature:
    Only admins that are not impersonating anybody can use the AI assistant

    Scenario: An admin sees the assistant button and opens the panel
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then I see a text "data-assistant-toggle"
        Then I see a text "assistant-panel"
        When I go to "/es/assistant/panel"
        Then the response status should be 200
        Then I see a button with name "Enviar"

    Scenario: An owner neither sees the button nor opens the panel
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        Given user "owner@acme.com" with role "ROLE_OWNER" of account "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        When I am logged as "owner@acme.com"
        When I go to "/es/profile"
        Then I do not see a text "data-assistant-toggle"
        When I go to "/es/assistant/panel"
        Then the response status should be 403

    Scenario: A user cannot open the panel
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/assistant/panel"
        Then the response status should be 403

    Scenario: An admin impersonating an account cannot use the assistant
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "Acme"
        When I go to "/es/profile"
        Then I do not see a text "data-assistant-toggle"
        When I go to "/es/assistant/panel"
        Then the response status should be 403

    Scenario: A super admin impersonating another admin cannot use the assistant
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given user "admin2@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/?_switch_user=admin2@dediez.software"
        When I go to "/es/profile"
        Then I do not see a text "data-assistant-toggle"
        When I go to "/es/assistant/panel"
        Then the response status should be 403

    Scenario: An admin does not see the conversation of another admin
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "other@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Secretos del otro" with id "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "other@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/thread"
        Then the response status should be 404
        When I go to "/es/assistant/panel?conversationId=0190f3a4-1b2c-7d3e-8f40-0000000000c1"
        Then the response status should be 200
        Then I do not see a text "Secretos del otro"

    Scenario Outline: An owner cannot use any endpoint of the assistant
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        Given user "owner@acme.com" with role "ROLE_OWNER" of account "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        When I am logged as "owner@acme.com"
        When I go to "<path>"
        Then the response status should be 403

        Examples:
            | path                                                                   |
            | /es/assistant/conversations                                            |
            | /es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/thread |
            | /es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/status |

    Scenario Outline: An admin impersonating an account cannot use any endpoint of the assistant
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        Given conversation "Mi conversación" with id "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "admin@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "Acme"
        When I go to "<path>"
        Then the response status should be 403

        Examples:
            | path                                                                   |
            | /es/assistant/conversations                                            |
            | /es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/thread |
            | /es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/status |

    Scenario Outline: A super admin impersonating another admin cannot use any endpoint of the assistant
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given user "admin2@dediez.software" with role "ROLE_ADMIN" exists
        Given conversation "Del otro admin" with id "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "admin2@dediez.software" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/?_switch_user=admin2@dediez.software"
        When I go to "<path>"
        Then the response status should be 403

        Examples:
            | path                                                                   |
            | /es/assistant/conversations                                            |
            | /es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/thread |
            | /es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/status |

    Scenario: An owner can neither send messages nor execute or discard plans
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Acme" with id "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        Given user "owner@acme.com" with role "ROLE_OWNER" of account "0190f3a4-1b2c-7d3e-8f40-00000000ac01" exists
        Given a proposed plan with id "0190f3a4-1b2c-7d3e-8f40-0000000000d1" in conversation "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "admin@dediez.software" exists
        When I am logged as "owner@acme.com"
        When I post the message "Hola" to the assistant
        Then the response status should be 403
        When I execute the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1"
        Then the response status should be 403
        When I discard the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1"
        Then the response status should be 403
        Then the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1" has status "proposed"

    Scenario: A super admin impersonating the owner of a plan cannot execute it
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given user "admin2@dediez.software" with role "ROLE_ADMIN" exists
        Given a proposed plan with id "0190f3a4-1b2c-7d3e-8f40-0000000000d1" in conversation "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "admin2@dediez.software" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/?_switch_user=admin2@dediez.software"
        When I execute the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1"
        Then the response status should be 403
        When I post the message "Hola" to the assistant
        Then the response status should be 403
        Then the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1" has status "proposed"

    Scenario: An admin can neither read, write to, execute nor discard what belongs to another admin
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "other@dediez.software" with role "ROLE_ADMIN" exists
        Given a proposed plan with id "0190f3a4-1b2c-7d3e-8f40-0000000000d1" in conversation "0190f3a4-1b2c-7d3e-8f40-0000000000c1" of user "other@dediez.software" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/assistant/conversations/0190f3a4-1b2c-7d3e-8f40-0000000000c1/status"
        Then the response status should be 404
        When I execute the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1"
        Then the response status should be 404
        When I discard the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1"
        Then the response status should be 404
        Then the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1" has status "proposed"
        When I go to "/es/assistant/panel"
        When I submit a form with "text:Dime los secretos|conversationId:0190f3a4-1b2c-7d3e-8f40-0000000000c1" and button "Enviar"
        Then the response status should be 404
        Then the plan "0190f3a4-1b2c-7d3e-8f40-0000000000d1" has status "proposed"
