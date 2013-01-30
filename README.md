nanni
=====

Nice Annotation Interface for text

Nathan Schneider (nschneid@cs.cmu.edu)

Status: IN DEVELOPMENT


# Goals

 * modular specification of multiple forms of annotation
 * multiple users (annotators)
 * short items (e.g. sentences)
 * everything stored in text files
 * written in Javascript and PHP
 * works in Firefox

# Preliminary

 * annotation persistence (for an item and user); not yet supported for 
   (preposition) tags. overridden with &new.
 * version history: viewing previous annotations of an item, including multiple 
   users' annotations side-by-side
 * ability to log in as a compound user: e.g. annotations for a user 'alice+bob' 
   can be edited by either 'alice' or 'bob' if they specify &u=alice+bob
 * previous/next buttons that do not save anything
     - TODO: the button should be red on hover if there are unsaved changes, 
       defined as: (a) the annotation in the text box differs from the user's 
       latest annotation, and (b) if &new, the user has modified the text box 
       since page load
     - turned off with &nonav. TODO: if &nonav is specified, submit button should 
       save but not continue to the next item.

# Upcoming features

 * item index for more flexible navigation/status summary

# Possible features

 * searching of items/annotations
 * user groups
 * annotation queues (data assigned to multiple users with minimum/maximum 
   number of annotations per item)
