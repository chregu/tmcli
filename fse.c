#include <CoreServices/CoreServices.h>
#include <unistd.h>
#include <stdio.h>

void myCallbackFunction(
    ConstFSEventStreamRef streamRef,
    void *clientCallBackInfo,
    size_t numEvents,
    void *eventPaths,
    const FSEventStreamEventFlags eventFlags[],
    const FSEventStreamEventId eventIds[])
{
    int i;
    char **paths = eventPaths;
 
    // printf("Callback called\n");
    for (i=0; i<numEvents; i++) {
        int count;
        /* flags are unsigned long, IDs are uint64_t */
        printf("%llu:%s:%lu\n", eventIds[i], paths[i], eventFlags[i]);
   }
}

int
main(int argc, char *argv[])
{
    /* Define variables and create a CFArray object containing
       CFString objects containing paths to watch.
     */
    CFStringRef mypath = CFSTR("/");
    CFArrayRef pathsToWatch = CFArrayCreate(NULL, (const void **)&mypath, 1, NULL);
    void *callbackInfo = NULL; // could put stream-specific data here.
    FSEventStreamRef stream;
    CFAbsoluteTime latency = 3.0; /* Latency in seconds */
 
    /* Create the stream, passing in a callback, */
    stream = FSEventStreamCreate(NULL,
        &myCallbackFunction,
        callbackInfo,
        pathsToWatch,
        atoi(argv[2]), /* Or a previous event ID */
        latency,
        kFSEventStreamCreateFlagNone /* Flags explained in reference */
);

    /* Create the stream before calling this. */
    FSEventStreamScheduleWithRunLoop(stream, CFRunLoopGetCurrent(),         kCFRunLoopDefaultMode);
	FSEventStreamStart(stream);
	//CFRunLoopRun();
	CFRunLoopRunInMode(kCFRunLoopDefaultMode,atoi(argv[1]),false);
	//sleep(10);
}

